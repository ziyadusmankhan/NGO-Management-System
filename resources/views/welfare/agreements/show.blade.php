@extends('layouts.app')

@section('title', 'Welfare Agreement')

@section('content')
<div style="max-width:900px;margin:0 auto;padding:24px 0;">

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>The agreement action could not be completed.</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            @if (old('signature_party') && !$agreementActionError)
                <p class="mb-0 mt-2">Your name has been kept in the submitted form. If you retry, select the evidence file again.</p>
            @endif
        </div>
    @endif

    @if (session('success'))
        <div style="padding:14px;margin-bottom:18px;border:1px solid var(--legacy-border-b7dfca, #b7dfca);border-radius:8px;background:var(--legacy-surface-f1fbf5, #f1fbf5);color:var(--legacy-text-176b4d, #176b4d);">
            {{ session('success') }}
        </div>
    @endif

    @if (session('info'))
        <div style="padding:14px;margin-bottom:18px;border:1px solid var(--legacy-border-ccd5d0, #ccd5d0);border-radius:8px;background:var(--legacy-surface-f7faf8, #f7faf8);">
            {{ session('info') }}
        </div>
    @endif

    <div style="margin-bottom:22px;">
        <h1 style="margin:0 0 8px;">Welfare Agreement</h1>
        <p style="color:var(--legacy-text-6d7571, #6d7571);margin:0;">
            Agreement {{ $agreement->agreement_number }}
        </p>
    </div>

    <div style="background:var(--legacy-surface-fff, #fff);border:1px solid var(--legacy-border-e2e7e4, #e2e7e4);border-radius:12px;padding:24px;margin-bottom:20px;">

        <p>
            <strong>Application:</strong>
            {{ $application->application_number }}
        </p>

        <p>
            <strong>Status:</strong>
            {{ ucfirst($agreement->status) }}
        </p>

        <p>
            <strong>Final approved amount:</strong>
            PKR {{ \App\Domain\Finance\Support\Money::format($agreement->approved_amount) }}
        </p>

        <p>
            <strong>Agreement date:</strong>
            {{ $agreement->agreement_date?->format('d M Y') ?? 'Not set' }}
        </p>

        <p>
            <strong>Effective date:</strong>
            {{ $agreement->effective_date?->format('d M Y') ?? 'Not set' }}
        </p>

        <hr style="border:0;border-top:1px solid var(--legacy-border-e2e7e4, #e2e7e4);margin:20px 0;">

        <h2 style="font-size:18px;">Agreement terms</h2>
        <div style="white-space:pre-wrap;line-height:1.7;">{{ $agreement->terms }}</div>

        @if ($agreement->notes)
            <hr style="border:0;border-top:1px solid var(--legacy-border-e2e7e4, #e2e7e4);margin:20px 0;">

            <h2 style="font-size:18px;">Internal notes</h2>
            <div style="white-space:pre-wrap;line-height:1.7;">{{ $agreement->notes }}</div>
        @endif
    </div>

    <div style="background:var(--legacy-surface-f7faf8, #f7faf8);border:1px solid var(--legacy-border-e2e7e4, #e2e7e4);border-radius:12px;padding:20px;margin-bottom:20px;">
        <strong>Signature status</strong>

        <p>
            Beneficiary:
            {{ $agreement->beneficiary_signed_at ? 'Signed' : 'Not signed' }}

        </p>

        @if ($agreement->beneficiary_document_path)
            @if(auth()->user()?->hasPermission('welfare.agreements.view'))<a
                href="{{ route('welfare.applications.agreement.evidence', [
                    'application' => $application,
                    'party' => 'beneficiary',
                ]) }}"
                style="color:var(--legacy-text-176b4d, #176b4d);"
            >
                View beneficiary signature evidence
            </a>@endif
        @endif

        <p>
            Organization:
            {{ $agreement->organization_signed_at ? 'Signed' : 'Not signed' }}
        </p>

        @if ($agreement->organization_document_path)
            @if(auth()->user()?->hasPermission('welfare.agreements.view'))<a
                href="{{ route('welfare.applications.agreement.evidence', [
                    'application' => $application,
                    'party' => 'organization',
                ]) }}"
                style="color:var(--legacy-text-176b4d, #176b4d);"
            >
                View organization signature evidence
            </a>@endif
        @endif

        
        <p>
            @if ($agreement->status === 'signed')
                Both signatures and their evidence have been recorded.
            @elseif ($agreement->status === 'prepared')
                This agreement is prepared for signing. Record each signature only after
                receiving the signed document and verifying the signature.
            @elseif ($agreement->status === 'draft')
                This agreement is still a draft and is not ready for signature.
            @else
                Current agreement status: {{ ucfirst($agreement->status) }}.
            @endif
        </p>

    </div>
    
    <!-- SIGNATURE AUDIT TRAIL -->
        <div style="background:var(--legacy-surface-fff, #fff);border:1px solid var(--legacy-border-e2e7e4, #e2e7e4);border-radius:12px;padding:24px;margin-bottom:20px;">

            <h2 style="font-size:18px;margin-top:0;">Signature Verification Audit Trail</h2>

            <p style="color:var(--legacy-text-6d7571, #6d7571);line-height:1.6;">
                This section records the signatory, verification staff member,
                timestamp, and private evidence status for each party.
            </p>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:18px;">

                <div style="background:var(--legacy-surface-f7faf8, #f7faf8);border:1px solid var(--legacy-border-e2e7e4, #e2e7e4);border-radius:9px;padding:16px;">
                    <h3 style="font-size:16px;margin-top:0;">Beneficiary</h3>

                    <p>
                        <strong>Signatory:</strong><br>
                        {{ $agreement->beneficiary_signatory_name ?? 'Not recorded' }}
                    </p>

                    <p>
                        <strong>Recorded at:</strong><br>
                        {{ $agreement->beneficiary_signed_at?->format('d M Y H:i') ?? 'Not recorded' }}
                    </p>

                    <p>
                        <strong>Verified by:</strong><br>
                        {{ $agreement->beneficiarySignatureVerifier?->name ?? 'Not recorded' }}
                    </p>

                    <p style="margin-bottom:0;">
                        <strong>Evidence:</strong><br>
                        {{ $agreement->beneficiary_document_path ? 'Stored privately' : 'Not uploaded' }}
                    </p>
                </div>

                <div style="background:var(--legacy-surface-f7faf8, #f7faf8);border:1px solid var(--legacy-border-e2e7e4, #e2e7e4);border-radius:9px;padding:16px;">
                    <h3 style="font-size:16px;margin-top:0;">Organization</h3>

                    <p>
                        <strong>Signatory:</strong><br>
                        {{ $agreement->organization_signatory_name ?? 'Not recorded' }}
                    </p>

                    <p>
                        <strong>Recorded at:</strong><br>
                        {{ $agreement->organization_signed_at?->format('d M Y H:i') ?? 'Not recorded' }}
                    </p>

                    <p>
                        <strong>Verified by:</strong><br>
                        {{ $agreement->organizationSignatureVerifier?->name ?? 'Not recorded' }}
                    </p>

                    <p style="margin-bottom:0;">
                        <strong>Evidence:</strong><br>
                        {{ $agreement->organization_document_path ? 'Stored privately' : 'Not uploaded' }}
                    </p>
                </div>

            </div>
        </div>

    @if (auth()->user()?->hasPermission('welfare.agreements.manage'))

            <div style="background:var(--legacy-surface-fff, #fff);border:1px solid var(--legacy-border-e2e7e4, #e2e7e4);border-radius:12px;padding:24px;margin-bottom:20px;">

                <h2 style="font-size:18px;margin-top:0;">Agreement Actions</h2>

                @if ($agreementActionError)
                    <div class="alert alert-warning" role="alert">
                        {{ $agreementActionError }}
                    </div>
                @elseif ($agreement->status === 'draft')

                    <p style="line-height:1.6;color:var(--legacy-text-6d7571, #6d7571);">
                        Mark the draft as prepared only after its contents have been reviewed
                        and the document is ready for signing.
                    </p>

                    <form
                        method="POST"
                        action="{{ route('welfare.applications.agreement.prepare', $application) }}"
                        onsubmit="return confirm('Mark this agreement as prepared for signing?')"
                    >
                        @csrf

                        <button
                            type="submit"
                            style="padding:11px 18px;border:0;border-radius:8px;background:var(--legacy-surface-176b4d, #176b4d);color:white;font-weight:600;cursor:pointer;"
                        >
                            Mark as Prepared
                        </button>
                    </form>

                @elseif ($agreement->status === 'prepared')

                    <p style="line-height:1.6;color:var(--legacy-text-6d7571, #6d7571);">
                        Record a signature only after the signed agreement document has
                        been received and the signatory's signature has been verified.
                        These forms record verification details; they do not create
                        electronic signatures.
                    </p>

                    @if (!$agreement->beneficiary_signed_at)

                        <form
                            method="POST"
                            enctype="multipart/form-data"
                            action="{{ route('welfare.applications.agreement.signature', ['application' => $application, 'party' => 'beneficiary']) }}"
                            style="border-top:1px solid var(--legacy-border-e2e7e4, #e2e7e4);padding-top:18px;margin-top:18px;"
                        >
                            @csrf

                            <h3 style="font-size:16px;">Beneficiary signature</h3>

                            <label for="beneficiary_signatory_name">
                                Beneficiary signatory's full name
                            </label>

                            <input
                                id="beneficiary_signatory_name"
                                type="text"
                                name="signatory_name"
                                maxlength="255"
                                required
                                value="{{ old('signature_party') === 'beneficiary' ? old('signatory_name') : '' }}"
                                style="display:block;width:100%;box-sizing:border-box;padding:11px;border:1px solid var(--legacy-border-ccd5d0, #ccd5d0);border-radius:7px;margin:8px 0 14px;"
                            >
                            <label for="beneficiary_document">
                                Signed agreement evidence (PDF, JPG or PNG; maximum 10 MB)
                            </label>

                            <input
                                id="beneficiary_document"
                                type="file"
                                name="document"
                                accept=".pdf,.jpg,.jpeg,.png"
                                required
                                style="display:block;width:100%;margin:8px 0 14px;"
                            >

                            <label style="display:flex;align-items:flex-start;gap:9px;margin-bottom:16px;line-height:1.5;">
                                <input
                                    type="checkbox"
                                    name="confirmed"
                                    value="1"
                                    required
                                    style="margin-top:4px;"
                                >
                                I confirm that the signed document was received and the
                                beneficiary's signature was verified.
                            </label>

                            <button
                                type="submit"
                                style="padding:11px 18px;border:0;border-radius:8px;background:var(--legacy-surface-176b4d, #176b4d);color:white;font-weight:600;cursor:pointer;"
                            >
                                Record Beneficiary Signature
                            </button>
                        </form>

                    @else

                        <p>
                            Beneficiary signature recorded for
                            <strong>{{ $agreement->beneficiary_signatory_name }}</strong>
                            on {{ $agreement->beneficiary_signed_at->format('d M Y H:i') }}.
                        </p>

                    @endif

                    @if (!$agreement->organization_signed_at)

                        <form
                            method="POST"
                            enctype="multipart/form-data"
                            action="{{ route('welfare.applications.agreement.signature', ['application' => $application, 'party' => 'organization']) }}"
                            style="border-top:1px solid var(--legacy-border-e2e7e4, #e2e7e4);padding-top:18px;margin-top:18px;"
                        >
                            @csrf

                            <h3 style="font-size:16px;">Organization signature</h3>

                            <label for="organization_signatory_name">
                                Organization signatory's full name
                            </label>

                            <input
                                id="organization_signatory_name"
                                type="text"
                                name="signatory_name"
                                maxlength="255"
                                required
                                value="{{ old('signature_party') === 'organization' ? old('signatory_name') : '' }}"
                                style="display:block;width:100%;box-sizing:border-box;padding:11px;border:1px solid var(--legacy-border-ccd5d0, #ccd5d0);border-radius:7px;margin:8px 0 14px;"
                            >

                            <label for="organization_document">
                                Signed agreement evidence (PDF, JPG or PNG; maximum 10 MB)
                            </label>

                            <input
                                id="organization_document"
                                type="file"
                                name="document"
                                accept=".pdf,.jpg,.jpeg,.png"
                                required
                                style="display:block;width:100%;margin:8px 0 14px;"
                            >

                            <label style="display:flex;align-items:flex-start;gap:9px;margin-bottom:16px;line-height:1.5;">
                                <input
                                    type="checkbox"
                                    name="confirmed"
                                    value="1"
                                    required
                                    style="margin-top:4px;"
                                >
                                I confirm that the signed document was received and the
                                organization signatory's signature was verified.
                            </label>

                            <button
                                type="submit"
                                style="padding:11px 18px;border:0;border-radius:8px;background:var(--legacy-surface-176b4d, #176b4d);color:white;font-weight:600;cursor:pointer;"
                            >
                                Record Organization Signature
                            </button>
                        </form>

                    @else

                        <p>
                            Organization signature recorded for
                            <strong>{{ $agreement->organization_signatory_name }}</strong>
                            on {{ $agreement->organization_signed_at->format('d M Y H:i') }}.
                        </p>

                    @endif

                @elseif ($agreement->status === 'signed')

                    <p style="color:var(--legacy-text-176b4d, #176b4d);font-weight:600;">
                        Both signature records are complete.
                    </p>

                    <p style="color:var(--legacy-text-6d7571, #6d7571);">
                        Signing does not itself authorize payment or create a welfare assistance record.
                    </p>

                @else

                    <p style="color:var(--legacy-text-6d7571, #6d7571);">
                        No actions are available for this agreement's current status.
                    </p>

                @endif

            </div>

        @endif

    @if (auth()->user()?->hasPermission('welfare.agreements.view'))
            @if(auth()->user()?->hasPermission('welfare.agreements.view'))<a
                href="{{ route('welfare.applications.agreement.print', $application) }}"
                style="display:inline-block;margin-right:12px;color:var(--legacy-text-176b4d, #176b4d);text-decoration:none;"
            >
                Print Agreement
            </a>@endif
        @endif
        
    @if(auth()->user()?->hasPermission('welfare.assistance.view'))<a
        href="{{ route('welfare.applications.show', $application) }}"
        style="color:var(--legacy-text-176b4d, #176b4d);text-decoration:none;"
    >
        &larr; Return to application
    </a>@endif
</div>
    @can('welfare.assistance.view')
        <div class="card mt-4"><div class="card-body">
            <h2 class="h5">Welfare assistance</h2>
            @if($assistance)
                @if($assistance->trashed())
                    <p>Assistance {{ $assistance->assistance_number }} is archived. This application cannot create another assistance.</p>
                @else
                    @if(auth()->user()?->hasPermission('welfare.assistance.view'))<a href="{{ route('welfare.assistance.show', $assistance) }}">{{ $assistance->assistance_number }} · {{ ucfirst($assistance->status) }}</a>@endif
                @endif
            @elseif($assistanceError)
                <p>{{ $assistanceError }}</p>
            @else
                @can('welfare.assistance.manage')
                    <p>Create a pending assistance using this agreement's amount and the application's beneficiary, purpose and authorized fund. No payment is created.</p>
                    @if(auth()->user()?->hasPermission('welfare.assistance.view') && auth()->user()?->hasPermission('welfare.assistance.manage'))<a class="btn btn-success" href="{{ route('welfare.assistance.create', ['application_id' => $application->id]) }}">Create assistance</a>@endif
                @endcan
            @endif
        </div></div>
    @endcan
@endsection
