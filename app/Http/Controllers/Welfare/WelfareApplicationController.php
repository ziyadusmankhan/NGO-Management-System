<?php

namespace App\Http\Controllers\Welfare;

use App\Domain\Finance\Support\Money;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Welfare\Applications\Models\WelfareApplication;
use App\Domain\Welfare\Applications\Models\WelfareApplicationReview;
use App\Domain\Welfare\Beneficiaries\Models\Beneficiary;
use App\Http\Controllers\Controller;
use App\Http\Requests\Welfare\StoreWelfareApplicationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WelfareApplicationController extends Controller
{
    /**
     * Application queue.
     */
    public function index(): View
    {
        $query = WelfareApplication::query()
            ->with([
                'beneficiary',
                'fund',
            ]);

        /*
         * Search
         */
        if (request()->filled('search')) {
            $search = trim(request('search'));

            $query->where(function ($q) use ($search) {
                $q->where(
                    'application_number',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'request_type',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'purpose_details',
                    'like',
                    "%{$search}%"
                )
                ->orWhereHas(
                    'beneficiary',
                    function ($beneficiaryQuery) use ($search) {
                        $beneficiaryQuery
                            ->where(
                                'full_name',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'beneficiary_number',
                                'like',
                                "%{$search}%"
                            );
                    }
                );
            });
        }

        /*
         * Stage filter
         */
        if (request()->filled('stage')) {
            $query->where(
                'status',
                request('stage')
            );
        }

        /*
         * Category filter
         */
        if (request()->filled('category')) {
            $query->where(
                'category',
                request('category')
            );
        }

        /*
         * Priority filter
         */
        if (request()->filled('priority')) {
            $query->where(
                'priority',
                request('priority')
            );
        }

        $applications = $query
            ->latest('submitted_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        /*
         * Dashboard statistics.
         *
         * These are based on applications submitted
         * during the current year.
         */
        $year = now()->year;

        $totalApplications = WelfareApplication::query()
            ->whereYear('submitted_at', $year)
            ->count();

        $underVerification = WelfareApplication::query()
            ->whereYear('submitted_at', $year)
            ->where('status', 'verification')
            ->count();

        $awaitingApproval = WelfareApplication::query()
            ->whereYear('submitted_at', $year)
            ->whereIn('status', [
                'recommendation',
                'general_secretary_review',
                'committee_review',
            ])
            ->count();

        $approved = WelfareApplication::query()
            ->whereYear('submitted_at', $year)
            ->where('status', 'approved')
            ->count();

        $rejected = WelfareApplication::query()
            ->whereYear('submitted_at', $year)
            ->where('status', 'rejected')
            ->count();

        return view(
            'welfare.applications.index',
            compact(
                'applications',
                'totalApplications',
                'underVerification',
                'awaitingApproval',
                'approved',
                'rejected'
            )
        );
    }

    /**
     * Application creation form.
     */
    public function create(): View
    {
        /*
         * Only beneficiaries who have passed both
         * verification and approval may submit applications.
         */
        $beneficiaries = Beneficiary::query()
            ->where('verification_status', 'verified')
            ->where('approval_status', 'approved')
            ->orderBy('full_name')
            ->get();

        /*
         * Funding sources shown to the applicant.
         */
        $funds = Fund::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view(
            'welfare.applications.create',
            compact(
                'beneficiaries',
                'funds'
            )
        );
    }

    /**
     * Store a new welfare application.
     */
    public function store(
        StoreWelfareApplicationRequest $request
    ): RedirectResponse {
        $data = $request->validated();

        /*
         * Re-check the beneficiary on the server.
         *
         * The create page is never trusted by itself.
         */
        $beneficiary = Beneficiary::query()
            ->whereKey($data['beneficiary_id'])
            ->firstOrFail();

        if (
            $beneficiary->verification_status !== 'verified'
            || $beneficiary->approval_status !== 'approved'
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'beneficiary_id' =>
                        'The selected beneficiary must be verified and approved before an application can be submitted.',
                ]);
        }

        /*
         * Validate the selected fund again.
         */
        $fund = null;

        if (!empty($data['fund_id'])) {
            $fund = Fund::query()
                ->whereKey($data['fund_id'])
                ->firstOrFail();

            if ($fund->status !== 'active') {
                return back()
                    ->withInput()
                    ->withErrors([
                        'fund_id' =>
                            'The selected funding source is not active.',
                    ]);
            }
        }

        /*
         * Calculate the organization's contribution.
         *
         * We do not trust a client-provided value for this.
         */
        $requestedAmount =
            Money::minor($data['requested_amount']);

        $applicantContribution =
            Money::minor($data['applicant_contribution'] ?? 0);

        if ($applicantContribution > $requestedAmount) {
            return back()
                ->withInput()
                ->withErrors([
                    'applicant_contribution' =>
                        'Applicant contribution cannot exceed the requested amount.',
                ]);
        }

        $organizationContribution =
            Money::decimal(Money::add($requestedAmount, -$applicantContribution));

        /*
         * Create the application atomically.
         */
        $application = DB::transaction(
            function () use (
                $data,
                $organizationContribution
            ) {
                $lastApplication =
                    WelfareApplication::withTrashed()
                        ->orderByDesc('id')
                        ->lockForUpdate()
                        ->first();

                $nextNumber =
                    ($lastApplication?->id ?? 0) + 1;

                $data['application_number'] =
                    'APP-' . str_pad(
                        $nextNumber,
                        5,
                        '0',
                        STR_PAD_LEFT
                    );

                $data['applicant_contribution'] =
                    Money::decimal(Money::minor($data['applicant_contribution'] ?? 0));

                $data['organization_contribution'] =
                    $organizationContribution;

                /*
                 * New applications always start here.
                 */
                $data['status'] = 'submitted';

                $data['submitted_at'] = now();

                $data['submitted_by'] =
                    auth()->id();

                $data['has_supporting_document'] =
                    (bool) (
                        $data['has_supporting_document']
                        ?? false
                    );

                return WelfareApplication::create($data);
            }
        );

        /*
         * Store supporting documents.
         */
        if ($request->hasFile('supporting_documents')) {

            foreach (
                $request->file('supporting_documents')
                as $file
            ) {
                $file->store(
                    'welfare/applications/' . $application->id,
                    'private'
                );
            }

            $application->update([
                'has_supporting_document' => true,
            ]);
        }

        return redirect()
            ->route(
                'welfare.applications.show',
                $application
            )
            ->with(
                'success',
                'Welfare application submitted successfully.'
            );
    }

    /**
         * Show application details.
         */
        /**
     * Show application details.
     */
    public function show(
        WelfareApplication $application
    ): View {
        $application->load([
            'beneficiary',
            'fund',
            'submittedBy',
            'recommendedBy',
            'approvedBy',
            'rejectedBy',
            'agreement',
            'assistanceIncludingArchived',

        'verifications' => function ($query) {
            $query
                ->latest('id')
                ->with([
                    'assignedTo',
                    'verifiedBy',
                ]);
        },
        'reviews' => function ($query) {
            $query
                ->orderBy('id')
                ->with('decidedBy');
        },
    ]);

        $documents = collect();

        $directory = 'welfare/applications/' . $application->id;

        if (Storage::disk('private')->exists($directory)) {
            $documents = collect(
                Storage::disk('private')->files($directory)
            )->map(function ($path) use ($application) {
                return [
                    'name' => basename($path),
                    'url' => route(
                        'welfare.applications.documents.show',
                        [$application, basename($path)]
                    ),
                    'size' => Storage::disk('private')->size($path),
                    'extension' => strtolower(
                        pathinfo($path, PATHINFO_EXTENSION)
                    ),
                ];
            });
        }

        return view(
            'welfare.applications.show',
            compact(
                'application',
                'documents'
            )
        );
    }

    /** Stream a private application document to an authorized staff member. */
    public function document(
        WelfareApplication $application,
        string $document
    ): StreamedResponse {
        abort_unless(
            $document !== '' && basename($document) === $document,
            404
        );

        $path = 'welfare/applications/' . $application->id . '/' . $document;

        abort_unless(Storage::disk('private')->exists($path), 404);

        return Storage::disk('private')->response(
            $path,
            $document,
            ['Cache-Control' => 'private, no-store']
        );
    }

    /**
     * Implementation Committee recommendation.
     *
     * The field verification report must be completed first. A positive
     * recommendation advances to the General Secretary; a return sends the
     * application back for a new field-verification assignment.
     */
    public function implementationDecision(
        Request $request,
        WelfareApplication $application
    ): RedirectResponse {
        $data = $request->validate([
            'decision' => [
                'required',
                'in:approve,return,reject',
            ],
            'recommended_amount' => [
                'required_if:decision,approve',
                'nullable',
                'string',
                'regex:/^\d{1,12}(?:\.\d{1,2})?$/',
                'numeric',
                'min:0.01',
                'max:999999999999.99',
            ],
            'notes' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);

        DB::transaction(function () use ($application, $data) {
            $locked = WelfareApplication::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertDecisionActor($locked, 'recommendation', 'Implementation Committee');
            $this->assertEligibleBeneficiary($locked);

            if (!$locked->verifications()->where('workflow_status', 'completed')->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'A completed field verification is required before the committee can make a recommendation.',
                ]);
            }

            $decision = $data['decision'];
            $nextStatus = match ($decision) {
                'approve' => 'general_secretary_review',
                'return' => 'submitted',
                'reject' => 'rejected',
            };

            $amount = $decision === 'approve'
                ? Money::decimal(Money::minor($data['recommended_amount']))
                : null;

            $this->recordApplicationReview(
                $locked,
                'implementation_committee',
                $decision,
                $amount,
                $data['notes'],
                [
                    'from_status' => 'recommendation',
                    'to_status' => $nextStatus,
                ]
            );

            $updates = [
                'status' => $nextStatus,
            ];

            if ($decision === 'approve') {
                $updates += [
                    'recommended_amount' => $amount,
                    'recommendation_notes' => $data['notes'],
                    'recommended_by' => auth()->id(),
                    'recommended_at' => now(),
                ];
            } elseif ($decision === 'reject') {
                $updates += [
                    'rejected_by' => auth()->id(),
                    'rejected_at' => now(),
                ];
            }

            $locked->update($updates);
        });

        return redirect()
            ->route('welfare.applications.show', $application)
            ->with('success', match ($data['decision']) {
                'approve' => 'Implementation Committee recommendation recorded and sent to the General Secretary.',
                'return' => 'Application returned to the field-verification queue.',
                'reject' => 'Application rejected by the Implementation Committee.',
            });
    }

    /**
     * General Secretary review.
     */
    public function secretaryReview(
        Request $request,
        WelfareApplication $application
    ): RedirectResponse {
        $data = $request->validate([
            'decision' => [
                'required',
                'in:approve,return,reject',
            ],
            'notes' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);

        DB::transaction(function () use ($application, $data) {
            $locked = WelfareApplication::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertDecisionActor($locked, 'general_secretary_review', 'General Secretary');
            $this->assertEligibleBeneficiary($locked);

            if (!$locked->reviews()->where('stage', 'implementation_committee')->where('decision', 'approve')->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'An Implementation Committee recommendation is required before General Secretary review.',
                ]);
            }

            $decision = $data['decision'];
            $nextStatus = match ($decision) {
                'approve' => 'committee_review',
                'return' => 'recommendation',
                'reject' => 'rejected',
            };

            $this->recordApplicationReview(
                $locked,
                'general_secretary',
                $decision,
                null,
                $data['notes'],
                [
                    'from_status' => 'general_secretary_review',
                    'to_status' => $nextStatus,
                ]
            );

            $updates = ['status' => $nextStatus];

            if ($decision === 'reject') {
                $updates += [
                    'rejected_by' => auth()->id(),
                    'rejected_at' => now(),
                ];
            }

            $locked->update($updates);
        });

        return redirect()
            ->route('welfare.applications.show', $application)
            ->with('success', match ($data['decision']) {
                'approve' => 'General Secretary review completed. Application forwarded to the Decision-Making Committee.',
                'return' => 'Application returned to the Implementation Committee for reconsideration.',
                'reject' => 'Application rejected during General Secretary review.',
            });
    }

    /**
     * Decision-Making Committee final decision.
     */
    public function committeeDecision(
        Request $request,
        WelfareApplication $application
    ): RedirectResponse {
        $data = $request->validate([
            'decision' => [
                'required',
                'in:approve,return,reject',
            ],
            'approved_amount' => [
                'required_if:decision,approve',
                'nullable',
                'string',
                'regex:/^\d{1,12}(?:\.\d{1,2})?$/',
                'numeric',
                'min:0.01',
                'max:999999999999.99',
            ],
            'notes' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);

        DB::transaction(function () use ($application, $data) {
            $locked = WelfareApplication::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertDecisionActor($locked, 'committee_review', 'Decision-Making Committee');
            $this->assertEligibleBeneficiary($locked);

            if (!$locked->reviews()->where('stage', 'general_secretary')->where('decision', 'approve')->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'General Secretary review must be completed before the Decision-Making Committee can decide.',
                ]);
            }

            $decision = $data['decision'];
            $nextStatus = match ($decision) {
                'approve' => 'approved',
                'return' => 'general_secretary_review',
                'reject' => 'rejected',
            };

            $amount = $decision === 'approve'
                ? Money::decimal(Money::minor($data['approved_amount']))
                : null;

            $this->recordApplicationReview(
                $locked,
                'decision_making_committee',
                $decision,
                $amount,
                $data['notes'],
                [
                    'from_status' => 'committee_review',
                    'to_status' => $nextStatus,
                ]
            );

            $updates = ['status' => $nextStatus];

            if ($decision === 'approve') {
                // The existing recommended_amount field is the application's
                // canonical approved allocation amount; the review row also
                // preserves this final amount as an immutable decision record.
                $updates += [
                    'recommended_amount' => $amount,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ];
            } elseif ($decision === 'reject') {
                $updates += [
                    'rejected_by' => auth()->id(),
                    'rejected_at' => now(),
                ];
            }

            $locked->update($updates);
        });

        return redirect()
            ->route('welfare.applications.show', $application)
            ->with('success', match ($data['decision']) {
                'approve' => 'Decision-Making Committee approval recorded. The application is ready for the authorized agreement and disbursement workflow.',
                'return' => 'Application returned to the General Secretary for further review.',
                'reject' => 'Application rejected by the Decision-Making Committee.',
            });
    }

    /**
     * Prevent skipping workflow stages and keep approval independent.
     * The submitter cannot decide their own application, and a person who
     * decided at one stage cannot decide another stage on the same application.
     * The same stage may be revisited after a return.
     */
    private function assertDecisionActor(
        WelfareApplication $application,
        string $expectedStatus,
        string $stageLabel
    ): void {
        if ($application->status !== $expectedStatus) {
            throw ValidationException::withMessages([
                'status' => "This application is not awaiting the {$stageLabel} stage.",
            ]);
        }

        $actorId = (int) auth()->id();

        if ((int) $application->submitted_by === $actorId) {
            throw ValidationException::withMessages([
                'decision' => 'The person who submitted this application cannot decide its approval workflow.',
            ]);
        }

        $hasDecidedAnotherStage = $application->reviews()
            ->where('decided_by', $actorId)
            ->where('stage', '!=', $this->reviewStageForStatus($expectedStatus))
            ->exists();

        if ($hasDecidedAnotherStage) {
            throw ValidationException::withMessages([
                'decision' => 'For separation of duties, one person cannot make decisions at multiple stages of the same application.',
            ]);
        }
    }

    private function reviewStageForStatus(string $status): string
    {
        return match ($status) {
            'recommendation' => 'implementation_committee',
            'general_secretary_review' => 'general_secretary',
            'committee_review' => 'decision_making_committee',
            default => throw new \InvalidArgumentException('Unsupported application workflow status.'),
        };
    }

    private function assertEligibleBeneficiary(WelfareApplication $application): void
    {
        $beneficiary = $application->beneficiary;

        if (
            !$beneficiary
            || $beneficiary->verification_status !== 'verified'
            || $beneficiary->approval_status !== 'approved'
        ) {
            throw ValidationException::withMessages([
                'beneficiary' => 'The beneficiary is no longer verified and approved.',
            ]);
        }
    }

    private function recordApplicationReview(
        WelfareApplication $application,
        string $stage,
        string $decision,
        ?string $amount,
        string $notes,
        array $metadata = []
    ): WelfareApplicationReview {
        $lastId = (int) (WelfareApplicationReview::query()
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('id') ?? 0);

        return WelfareApplicationReview::create([
            'review_number' => 'REV-' . str_pad(
                $lastId + 1,
                5,
                '0',
                STR_PAD_LEFT
            ),
            'application_id' => $application->id,
            'stage' => $stage,
            'decision' => $decision,
            'recommended_amount' => $amount,
            'notes' => $notes,
            'decided_by' => auth()->id(),
            'decided_at' => now(),
            'metadata' => $metadata,
        ]);
    }
}
