<?php

namespace App\Http\Controllers\Welfare;

use App\Domain\Documents\Services\DocumentService;
use App\Domain\Documents\Services\UploadValidator;
use App\Domain\Users\Models\User;
use App\Domain\Welfare\Applications\Models\WelfareApplication;
use App\Domain\Welfare\Beneficiaries\Models\Beneficiary;
use App\Domain\Welfare\Verification\Models\BeneficiaryVerification;
use App\Domain\Welfare\Verification\Models\BeneficiaryVerificationEvidence;
use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class BeneficiaryVerificationController extends Controller
{
    /** Evidence upload rules (kept in one place). */
    private const EVIDENCE_MAX_FILES = 10;

    private const EVIDENCE_MAX_KB = 10240; // 10 MB per file

    private const EVIDENCE_DISK = 'local'; // private disk, never public

    /**
     * Field verification queue.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:assigned,in_progress,completed,overdue'],
            'priority' => ['nullable', 'in:high,normal'],
            'visit_from' => ['nullable', 'date'],
            'visit_to' => ['nullable', 'date', 'after_or_equal:visit_from'],
            'sort' => ['nullable', 'in:newest,visit_asc,visit_desc'],
        ]);

        $query = BeneficiaryVerification::query()
            ->whereNotNull('application_id')
            ->with([
                'beneficiary',
                'application',
                'assignedTo',
                'verifiedBy',
            ]);

        /*
         * Search.
         */
        $search = trim($filters['search'] ?? '');

        if ($search !== '') {
            $like = '%'.$search.'%';

            $query->where(function ($q) use ($like) {
                $q->where('verification_number', 'like', $like)
                    ->orWhereHas('beneficiary', function ($b) use ($like) {
                        $b->where('full_name', 'like', $like)
                            ->orWhere('beneficiary_number', 'like', $like);
                    })
                    ->orWhereHas('application', function ($a) use ($like) {
                        $a->where('application_number', 'like', $like);
                    })
                    ->orWhereHas('assignedTo', function ($u) use ($like) {
                        $u->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    });
            });
        }

        /*
         * Status filter. "overdue" is derived, not stored:
         * still open and the visit date has passed.
         */
        $status = $filters['status'] ?? null;

        if ($status === 'overdue') {
            $query->whereIn('workflow_status', ['assigned', 'in_progress'])
                ->whereDate('visit_date', '<', now()->toDateString());
        } elseif ($status) {
            $query->where('workflow_status', $status);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        /*
         * Visit date range.
         */
        if (! empty($filters['visit_from'])) {
            $query->whereDate('visit_date', '>=', $filters['visit_from']);
        }

        if (! empty($filters['visit_to'])) {
            $query->whereDate('visit_date', '<=', $filters['visit_to']);
        }

        /*
         * Sorting.
         */
        match ($filters['sort'] ?? 'newest') {
            'visit_asc' => $query->orderByRaw('visit_date is null')->orderBy('visit_date')->orderByDesc('id'),
            'visit_desc' => $query->orderByRaw('visit_date is null')->orderByDesc('visit_date')->orderByDesc('id'),
            default => $query->latest('id'),
        };

        $verifications = $query
            ->paginate(15)
            ->withQueryString();

        /*
         * Queue statistics.
         */
        $base = BeneficiaryVerification::query()
            ->whereNotNull('application_id');

        $pendingVerification = (clone $base)
            ->where('workflow_status', 'assigned')
            ->count();

        $inProgress = (clone $base)
            ->where('workflow_status', 'in_progress')
            ->count();

        $completedThisMonth = (clone $base)
            ->where('workflow_status', 'completed')
            ->whereYear('completed_at', now()->year)
            ->whereMonth('completed_at', now()->month)
            ->count();

        $overdue = (clone $base)
            ->whereIn('workflow_status', ['assigned', 'in_progress'])
            ->whereDate('visit_date', '<', now()->toDateString())
            ->count();

        return view(
            'welfare.verification.index',
            compact(
                'verifications',
                'pendingVerification',
                'inProgress',
                'completedThisMonth',
                'overdue'
            )
        );
    }

    /**
     * Assignment form.
     */
    public function create(): View
    {
        /*
         * Only submitted applications are waiting
         * for their first field verification.
         */
        $applications = WelfareApplication::query()
            ->where('status', 'submitted')
            ->with('beneficiary')
            ->orderBy('submitted_at')
            ->get();

        /*
         * Only verified + approved beneficiaries
         * can move into the welfare application workflow.
         */
        $applications = $applications
            ->filter(function (WelfareApplication $application) {
                $beneficiary = $application->beneficiary;

                return $beneficiary
                    && $beneficiary->verification_status === 'verified'
                    && $beneficiary->approval_status === 'approved';
            })
            ->values();

        /*
         * Active users who have beneficiary verification
         * permission are eligible field officers.
         */
        $officers = User::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->filter(function (User $user) {
                return $user->hasPermission('welfare.beneficiaries.verify');
            })
            ->values();

        return view(
            'welfare.verification.create',
            compact('applications', 'officers')
        );
    }

    /**
     * Create a new field verification assignment.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'application_id' => ['required', 'integer', 'exists:welfare_applications,id'],
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
            'visit_date' => ['required', 'date', 'after_or_equal:today'],
            'priority' => ['required', 'in:high,normal'],
            'method' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $application = WelfareApplication::query()
            ->with('beneficiary')
            ->findOrFail($data['application_id']);

        /*
         * Application must still be waiting
         * for field verification.
         */
        if ($application->status !== 'submitted') {
            return back()
                ->withInput()
                ->withErrors([
                    'application_id' => 'This application is not currently awaiting field verification.',
                ]);
        }

        /*
         * Beneficiary must still be verified and approved.
         */
        $beneficiary = $application->beneficiary;

        if (
            ! $beneficiary
            || $beneficiary->verification_status !== 'verified'
            || $beneficiary->approval_status !== 'approved'
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'application_id' => 'The beneficiary must remain verified and approved.',
                ]);
        }

        /*
         * The selected officer must be active and
         * authorized to conduct beneficiary verification.
         */
        $officer = User::query()
            ->whereKey($data['assigned_to'])
            ->where('status', 'active')
            ->first();

        if (
            ! $officer
            || ! $officer->hasPermission('welfare.beneficiaries.verify')
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'assigned_to' => 'The selected officer is not authorized for field verification.',
                ]);
        }

        /*
         * Prevent multiple active field verifications
         * for the same application.
         */
        $activeVerificationExists = BeneficiaryVerification::query()
            ->where('application_id', $application->id)
            ->whereIn('workflow_status', ['assigned', 'in_progress'])
            ->exists();

        if ($activeVerificationExists) {
            return back()
                ->withInput()
                ->withErrors([
                    'application_id' => 'This application already has an active field verification assignment.',
                ]);
        }

        $verification = DB::transaction(
            function () use ($data, $application) {
                $verification = BeneficiaryVerification::create([
                    'beneficiary_id' => $application->beneficiary_id,
                    'application_id' => $application->id,
                    'assigned_to' => $data['assigned_to'],
                    'assigned_at' => now(),
                    'visit_date' => $data['visit_date'],
                    'status' => 'pending',
                    'workflow_status' => 'assigned',
                    'priority' => $data['priority'],
                    'method' => $data['method'] ?? 'field_visit',
                    'notes' => $data['notes'] ?? null,
                ]);

                /*
                 * Generate VER-00001 style identifier
                 * from the newly created primary key.
                 */
                $verification->update([
                    'verification_number' => 'VER-'.str_pad($verification->id, 5, '0', STR_PAD_LEFT),
                ]);

                /*
                 * Move the application into the
                 * verification stage.
                 */
                $application->update([
                    'status' => 'verification',
                ]);

                return $verification;
            }
        );

        return redirect()
            ->route('welfare.verification.show', $verification)
            ->with('success', 'Field verification assigned successfully.');
    }

    /**
     * Show verification details.
     */
    public function show(BeneficiaryVerification $verification): View
    {
        /*
         * Only application-specific verifications
         * belong to this workflow.
         */
        abort_unless($verification->application_id !== null, 404);

        $verification->load([
            'beneficiary',
            'application.beneficiary',
            'assignedTo',
            'verifiedBy',
            'evidence.uploader',
        ]);

        $evidenceLimits = $this->evidenceLimits();

        return view(
            'welfare.verification.show',
            compact('verification', 'evidenceLimits')
        );
    }

    /**
     * Start a field verification.
     */
    public function start(BeneficiaryVerification $verification): RedirectResponse
    {
        if ($verification->workflow_status !== 'assigned') {
            return back()->withErrors([
                'status' => 'Only assigned verifications can be started.',
            ]);
        }

        /*
         * Only the assigned field officer may start
         * this verification.
         */
        if (! $this->isAssignedOfficer($verification)) {
            return back()->withErrors([
                'assigned_to' => 'Only the assigned field officer can start this verification.',
            ]);
        }

        $verification->update([
            'workflow_status' => 'in_progress',
            'started_at' => now(),
        ]);

        return back()->with(
            'success',
            'Field verification marked as in progress.'
        );
    }

    /**
     * Complete a field verification, store the uploaded
     * evidence and submit the recommendation to the
     * application workflow.
     */
    public function complete(
        Request $request,
        BeneficiaryVerification $verification
    ): RedirectResponse {
        if ($verification->workflow_status !== 'in_progress') {
            return back()->withErrors([
                'status' => 'Only verifications currently in progress can be completed.',
            ]);
        }

        /*
         * Only the assigned field officer may complete
         * this verification.
         */
        if (! $this->isAssignedOfficer($verification)) {
            return back()->withErrors([
                'assigned_to' => 'Only the assigned field officer can complete this verification.',
            ]);
        }

        $limits = $this->evidenceLimits();

        /*
         * NOTE: adjust field names / recommendation values below
         * if they differ from your form and DB column.
         */
        $rules = [
            'recommendation' => ['required', Rule::in(['approve', 'revise', 'reject'])],
            'findings' => ['required', 'string', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'recommendation_notes' => ['nullable', 'string', 'max:5000'],
            'checklist' => ['nullable', 'array'],
            'checklist.*' => ['nullable', 'boolean'],

            // Evidence is mandatory only when recommending approval
            // (matches the form's client-side rule).
            'evidence' => [
                Rule::requiredIf(fn () => $request->input('recommendation') === 'approve'),
                'array',
                'max:'.self::EVIDENCE_MAX_FILES,
            ],
            'evidence.*' => [
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'max:'.(int) floor($limits['per_file'] / 1024),
            ],
        ];

        $messages = [
            'evidence.required' => 'Please upload at least one photo or document as evidence before recommending approval.',
            'evidence.max' => 'You can upload a maximum of '.self::EVIDENCE_MAX_FILES.' evidence files.',
            'evidence.*.uploaded' => 'An evidence file could not be uploaded. The server limit is '
                .$this->formatBytes($limits['per_file']).' per file.',
            'evidence.*.mimes' => 'Evidence must be a PDF, JPG, PNG or WEBP file.',
            'evidence.*.max' => 'Each evidence file must be '.$this->formatBytes($limits['per_file']).' or smaller.',
        ];

        $data = $request->validate($rules, $messages);

        /** @var array<int, UploadedFile> $files */
        $files = array_values($request->file('evidence', []));

        /*
         * Normalise checklist to real booleans. "Photos / Evidence
         * attached" is decided by the actual uploads, not by a tick.
         */
        $checklist = [];

        foreach (array_keys(BeneficiaryVerification::CHECKLIST_ITEMS) as $key) {
            $checklist[$key] = $request->boolean("checklist.$key");
        }

        $checklist['photos_attached'] = count($files) > 0;

        $storedPaths = [];

        try {
            DB::transaction(function () use (
                $data,
                $checklist,
                $files,
                $verification,
                &$storedPaths
            ) {
                /*
                 * Re-read everything under lock so two submissions
                 * (double click, second tab) cannot both complete it.
                 */
                $locked = BeneficiaryVerification::query()
                    ->whereKey($verification->id)
                    ->lockForUpdate()
                    ->first();

                if (! $locked || $locked->workflow_status !== 'in_progress') {
                    throw new DomainException(
                        'Only verifications currently in progress can be completed.'
                    );
                }

                $application = $locked->application_id
                    ? WelfareApplication::query()
                        ->whereKey($locked->application_id)
                        ->lockForUpdate()
                        ->first()
                    : null;

                $beneficiary = Beneficiary::query()
                    ->find($locked->beneficiary_id);

                if (! $application || ! $beneficiary) {
                    throw new DomainException(
                        'The related welfare application could not be found.'
                    );
                }

                if (
                    $beneficiary->verification_status !== 'verified'
                    || $beneficiary->approval_status !== 'approved'
                ) {
                    throw new DomainException(
                        'The beneficiary is no longer verified and approved.'
                    );
                }

                if ($application->status !== 'verification') {
                    throw new DomainException(
                        'This application is not currently in the verification stage.'
                    );
                }

                /*
                 * Store evidence on the private disk.
                 */
                foreach ($files as $file) {
                    app(UploadValidator::class)->inspect($file);
                    $path = $file->store(
                        'welfare/verification/'.$locked->id,
                        self::EVIDENCE_DISK
                    );

                    if (! $path) {
                        throw new DomainException(
                            'An evidence file could not be saved. Please try again.'
                        );
                    }

                    $storedPaths[] = $path;

                    $locked->evidence()->create([
                        'uploaded_by' => auth()->id(),
                        'disk' => self::EVIDENCE_DISK,
                        'path' => $path,
                        'original_name' => mb_substr(
                            basename($file->getClientOriginalName()),
                            0,
                            255
                        ),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }

                $locked->update([
                    'verification_date' => now()->toDateString(),
                    'verified_by' => auth()->id(),
                    'status' => $data['recommendation'] === 'reject'
                        ? 'rejected'
                        : 'verified',
                    'workflow_status' => 'completed',
                    'completed_at' => now(),
                    'findings' => $data['findings'],
                    'checklist' => $checklist,
                    'notes' => $data['notes'] ?? null,
                    'recommendation' => $data['recommendation'],
                    'recommendation_notes' => $data['recommendation_notes'] ?? null,
                ]);

                /*
                 * The application now waits for the
                 * appropriate decision on the recommendation.
                 */
                $application->update([
                    'status' => 'recommendation',
                    'recommended_amount' => $application->requested_amount,
                    'recommendation_notes' => $data['recommendation_notes'] ?? null,
                    'recommended_by' => auth()->id(),
                    'recommended_at' => now(),
                ]);
            });
        } catch (Throwable $e) {
            /*
             * Roll back any files already written so we never
             * leave orphaned evidence on disk.
             */
            foreach ($storedPaths as $path) {
                Storage::disk(self::EVIDENCE_DISK)->delete($path);
            }

            if ($e instanceof DomainException) {
                return back()
                    ->withInput()
                    ->withErrors(['status' => $e->getMessage()]);
            }

            Log::error('Field verification completion failed', [
                'verification_id' => $verification->id,
                'exception' => $e,
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'status' => 'Something went wrong while saving the verification. Nothing was saved, please try again.',
                ]);
        }

        return redirect()
            ->route('welfare.verification.show', $verification)
            ->with(
                'success',
                'Field verification completed and recommendation submitted successfully.'
            );
    }

    /**
     * View (inline) or download an evidence file.
     * Files live on a private disk, so they are always
     * served through this authorised route.
     */
    public function evidence(
        Request $request,
        BeneficiaryVerification $verification,
        BeneficiaryVerificationEvidence $evidence
    ): Response {
        abort_unless($verification->application_id !== null, 404);
        abort_unless(
            (int) $evidence->verification_id === (int) $verification->id,
            404
        );

        $disk = Storage::disk($evidence->disk);

        abort_unless($disk->exists($evidence->path), 404);

        return app(DocumentService::class)->sourceDownload($request->user(), 'verification:'.$evidence->id);
    }

    /**
     * Only the assigned officer may work the verification.
     * Cast both sides: MySQL drivers can return ids as strings.
     */
    private function isAssignedOfficer(BeneficiaryVerification $verification): bool
    {
        return $verification->assigned_to !== null
            && (int) $verification->assigned_to === (int) auth()->id();
    }

    /**
     * Effective upload limits: the app limit capped by what
     * PHP will actually accept (upload_max_filesize / post_max_size).
     * Without this, an oversized request is silently dropped by PHP.
     *
     * @return array{per_file:int,total:int,max_files:int}
     */
    private function evidenceLimits(): array
    {
        $perFile = self::EVIDENCE_MAX_KB * 1024;

        $uploadMax = $this->iniBytes('upload_max_filesize');
        if ($uploadMax > 0) {
            $perFile = min($perFile, $uploadMax);
        }

        $postMax = $this->iniBytes('post_max_size');
        $total = $postMax > 0
            ? max($perFile, $postMax - 512 * 1024) // leave room for form fields
            : $perFile * self::EVIDENCE_MAX_FILES;

        return [
            'per_file' => $perFile,
            'total' => $total,
            'max_files' => self::EVIDENCE_MAX_FILES,
        ];
    }

    private function iniBytes(string $key): int
    {
        $value = trim((string) ini_get($key));

        if ($value === '' || $value === '-1') {
            return 0;
        }

        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }

    private function formatBytes(int $bytes): string
    {
        return $bytes >= 1048576
            ? rtrim(rtrim(number_format($bytes / 1048576, 1), '0'), '.').' MB'
            : max(1, (int) round($bytes / 1024)).' KB';
    }
}
