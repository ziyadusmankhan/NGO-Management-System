<?php

namespace App\Http\Controllers\Welfare;

use App\Domain\Welfare\Applications\Models\WelfareApplication;
use App\Domain\Welfare\Applications\Models\WelfareApplicationAgreement;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WelfareApplicationAgreementController extends Controller
{
    /**
     * Display the draft-agreement form.
     */
    public function create(
        WelfareApplication $application
    ): View|RedirectResponse {
        $application->loadMissing([
            'beneficiary',
            'agreement',
        ]);

        if ($application->agreement) {
            return redirect()
                ->route(
                    'welfare.applications.agreement.show',
                    $application
                )
                ->with('info', 'An agreement already exists for this application.');
        }

        $error = $this->agreementEligibilityError($application);

        if ($error !== null) {
            return redirect()
                ->route('welfare.applications.show', $application)
                ->withErrors(['agreement' => $error]);
        }

        return view(
            'welfare.agreements.create',
            compact('application')
        );
    }

    /**
     * Store one draft agreement for an approved application.
     */
    public function store(
        Request $request,
        WelfareApplication $application
    ): RedirectResponse {
        $data = $request->validate([
            'agreement_date' => [
                'nullable',
                'date',
            ],
            'effective_date' => [
                'nullable',
                'date',
            ],
            'terms' => [
                'required',
                'string',
                'max:20000',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        if (
            !empty($data['agreement_date'])
            && !empty($data['effective_date'])
            && $data['effective_date'] < $data['agreement_date']
        ) {
            throw ValidationException::withMessages([
                'effective_date' =>
                    'The effective date cannot precede the agreement date.',
            ]);
        }

        $agreement = DB::transaction(
            function () use ($application, $data) {
                $locked = WelfareApplication::query()
                    ->whereKey($application->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $error = $this->agreementEligibilityError($locked);

                if ($error !== null) {
                    throw ValidationException::withMessages([
                        'agreement' => $error,
                    ]);
                }

                // Enforce one agreement per application.
                if ($locked->agreement()->exists()) {
                    throw ValidationException::withMessages([
                        'agreement' =>
                            'An agreement already exists for this application.',
                    ]);
                }

                $amount = (float) $locked->recommended_amount;

                return WelfareApplicationAgreement::create([
                    'agreement_number' =>
                        'AGR-' . str_pad(
                            (string) $locked->id,
                            8,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'application_id' => $locked->id,
                    'approved_amount' => $amount,
                    'agreement_date' => $data['agreement_date'] ?? null,
                    'effective_date' => $data['effective_date'] ?? null,
                    'status' => 'draft',
                    'terms' => $data['terms'],
                    'notes' => $data['notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);
            }
        );

        return redirect()
            ->route(
                'welfare.applications.agreement.show',
                $application
            )
            ->with(
                'success',
                'Draft agreement created. No assistance or payment was created.'
            );
    }

    /**
     * Display an existing agreement.
     */
    public function show(
        WelfareApplication $application
    ): View {
        $agreement = $application->agreement()
            ->with([
                'beneficiarySignatureVerifier',
                'organizationSignatureVerifier',
            ])
            ->firstOrFail();

        return view(
            'welfare.agreements.show',
            compact('application', 'agreement')
        );
    }
    
    /**
     * Mark a draft agreement as prepared for signing.
     */
    public function prepare(
        WelfareApplication $application
    ): RedirectResponse {
        DB::transaction(function () use ($application) {
            $locked = WelfareApplication::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $error = $this->agreementEligibilityError($locked);

            if ($error !== null) {
                throw ValidationException::withMessages([
                    'agreement' => $error,
                ]);
            }

            $agreement = WelfareApplicationAgreement::query()
                ->where('application_id', $locked->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($agreement->status !== 'draft') {
                throw ValidationException::withMessages([
                    'agreement' => 'Only a draft agreement can be marked as prepared.',
                ]);
            }

            $agreement->update([
                'status' => 'prepared',
                'prepared_by' => auth()->id(),
                'prepared_at' => now(),
            ]);
        });

        return redirect()
            ->route('welfare.applications.agreement.show', $application)
            ->with(
                'success',
                'Agreement marked as prepared. No signatures, assistance, or payments were recorded.'
            );
    }

    
    /**
     * Record a signatory after receiving and verifying the signed document.
     *
     * This records evidence and verification details; it is not an
     * electronic signature.
     */
    public function recordSignature(
        Request $request,
        WelfareApplication $application,
        string $party
    ): RedirectResponse {
        if (!in_array($party, ['beneficiary', 'organization'], true)) {
            abort(404);
        }

        $data = $request->validate([
            'signatory_name' => [
                'required',
                'string',
                'max:255',
            ],
            'confirmed' => [
                'required',
                'accepted',
            ],
            'document' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:10240',
            ],
        ]);

        $storedPath = null;

        try {
            $result = DB::transaction(function () use (
                $application,
                $party,
                $data,
                &$storedPath
            ) {
                $locked = WelfareApplication::query()
                    ->whereKey($application->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $error = $this->agreementEligibilityError($locked);

                if ($error !== null) {
                    throw ValidationException::withMessages([
                        'agreement' => $error,
                    ]);
                }

                $agreement = WelfareApplicationAgreement::query()
                    ->where('application_id', $locked->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($agreement->status !== 'prepared') {
                    throw ValidationException::withMessages([
                        'agreement' =>
                            'Only a prepared agreement can receive signatures.',
                    ]);
                }

                $nameField = $party === 'beneficiary'
                    ? 'beneficiary_signatory_name'
                    : 'organization_signatory_name';

                $timestampField = $party === 'beneficiary'
                    ? 'beneficiary_signed_at'
                    : 'organization_signed_at';

                $pathField = $party === 'beneficiary'
                    ? 'beneficiary_document_path'
                    : 'organization_document_path';
                $verifiedByField = $party === 'beneficiary'
                    ? 'beneficiary_signature_verified_by'
                    : 'organization_signature_verified_by';

                $otherNameField = $party === 'beneficiary'
                    ? 'organization_signatory_name'
                    : 'beneficiary_signatory_name';

                if (
                    $agreement->{$timestampField} !== null
                    || $agreement->{$pathField} !== null
                ) {
                    throw ValidationException::withMessages([
                        'agreement' =>
                            'Signature evidence has already been recorded for this party.',
                    ]);
                }

                $signatoryName = trim($data['signatory_name']);

                if (
                    $agreement->{$otherNameField} !== null
                    && mb_strtolower($signatoryName)
                        === mb_strtolower(trim($agreement->{$otherNameField}))
                ) {
                    throw ValidationException::withMessages([
                        'signatory_name' =>
                            'The beneficiary and organization signatories must be different people.',
                    ]);
                }

                // Store the uploaded evidence on the private disk.
                $storedPath = $data['document']->store(
                    "welfare/agreements/{$agreement->id}/{$party}",
                    'private'
                );

                if (!$storedPath) {
                    throw new \RuntimeException(
                        'The signature evidence could not be stored.'
                    );
                }

                $agreement->update([
                    $nameField => $signatoryName,
                    $timestampField => now(),
                    $pathField => $storedPath,
                    $verifiedByField => auth()->id(),
                ]);

                $agreement->refresh();

                $bothSigned =
                    $agreement->beneficiary_signed_at !== null
                    && $agreement->organization_signed_at !== null
                    && $agreement->beneficiary_document_path !== null
                    && $agreement->organization_document_path !== null;

                if ($bothSigned) {
                    $agreement->update([
                        'status' => 'signed',
                        'fully_signed_at' => now(),
                    ]);
                }

                return [
                    'party' => $party,
                    'fully_signed' => $bothSigned,
                ];
            });
        } catch (\Throwable $exception) {
            // Remove an uploaded file if the database operation fails.
            if ($storedPath !== null) {
                Storage::disk('private')->delete($storedPath);
            }

            throw $exception;
        }

        $message = $result['fully_signed']
            ? 'Both signature records and their evidence have been saved. The agreement is marked as signed. No payment was created.'
            : ucfirst($result['party'])
                . ' signature evidence recorded. The agreement is waiting for the other signatory.';

        return redirect()
            ->route('welfare.applications.agreement.show', $application)
            ->with('success', $message);
    }

    
    /**
     * Display a print-ready copy of an existing agreement.
     */
    public function printable(
        WelfareApplication $application
    ): View {
        $application->loadMissing('beneficiary');

        $agreement = $application->agreement()->firstOrFail();

        return view(
            'welfare.agreements.print',
            compact('application', 'agreement')
        );
    }




    /**
     * Stream signature evidence from private storage to authorized staff.
     */
    public function evidence(
        WelfareApplication $application,
        string $party
    ): StreamedResponse {
        if (!in_array($party, ['beneficiary', 'organization'], true)) {
            abort(404);
        }

        $agreement = $application->agreement()->firstOrFail();

        if ($party === 'beneficiary') {
            $path = $agreement->beneficiary_document_path;
            $signedAt = $agreement->beneficiary_signed_at;
        } else {
            $path = $agreement->organization_document_path;
            $signedAt = $agreement->organization_signed_at;
        }

        if (!$path || !$signedAt) {
            abort(404);
        }

        // Accept files only from the expected party-specific directory.
        $expectedPrefix =
            "welfare/agreements/{$agreement->id}/{$party}/";

        if (!str_starts_with($path, $expectedPrefix)) {
            abort(404);
        }

        $disk = Storage::disk('private');

        if (!$disk->exists($path)) {
            abort(404);
        }

        return $disk->response(
            $path,
            basename($path),
            [
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ],
            'inline'
        );
    }



    /**
     * Check whether the current user may prepare this agreement.
     */
    private function agreementEligibilityError(
        WelfareApplication $application
    ): ?string {
        if (in_array(
                $application->application_number,
                ['APP-00005', 'APP-00010'],
                true
            )) {
                return 'This application is reserved for workflow testing and cannot be used for agreement signing or disbursement.';
            }
        if (
            $application->status !== 'approved'
            || !$application->approved_at
            || !$application->approved_by
        ) {
            return 'Only applications with recorded final approval can proceed to an agreement.';
        }

        if (
            !is_numeric($application->recommended_amount)
            || (float) $application->recommended_amount <= 0
        ) {
            return 'The application must have a valid final approved amount.';
        }

        $beneficiary = $application->beneficiary;

        if (
            !$beneficiary
            || $beneficiary->verification_status !== 'verified'
            || $beneficiary->approval_status !== 'approved'
        ) {
            return 'The beneficiary must remain verified and approved.';
        }

        if (
            (int) $application->submitted_by === (int) auth()->id()
        ) {
            return 'You cannot prepare an agreement for an application you submitted.';
        }

        return null;
    }
}
