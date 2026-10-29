<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $agreement->agreement_number }} - Welfare Agreement</title>

    <link rel="stylesheet" href="{{ asset('css/views/welfare-agreements-print-1.css') }}">
<link rel="stylesheet" href="{{ asset('css/foundation-print.css') }}"></head>

<body>

    <div class="actions">
        <button type="button" onclick="window.print()">
            Print / Save as PDF
        </button>

        @if(auth()->user()?->hasPermission('welfare.agreements.view'))<a href="{{ route('welfare.applications.agreement.show', $application) }}">
            Return to Agreement
        </a>@endif
    </div>

    <main class="document">

        <img class="watermark" src="{{ asset('images/falah-logo.jpeg') }}" alt="">

        <header class="letterhead">
            <img class="letterhead-logo"
                 src="{{ asset('images/falah-logo.jpeg') }}"
                 alt="Tanzeem Falah Naujawanan Kasso logo">

            <div class="letterhead-text">
                <p class="org-name">Tanzeem Falah Naujawanan Kasso</p>
                <p class="org-name-ur" lang="ur" dir="rtl">تنظیم فلاح نوجوانان کسو</p>
                <p class="org-committee">Amal Daramad Committee</p>
            </div>

            <div class="ref-box">
                <span class="ref-label">Agreement reference</span>
                <span class="ref-value">{{ $agreement->agreement_number }}</span>
            </div>
        </header>

        <div class="title-block">
            <h1>WELFARE ASSISTANCE AGREEMENT</h1>
            <div class="title-rule"></div>
        </div>

        @if ($application->isTestApplication())
            <div class="warning">
                TEST RECORD — NOT A LEGAL AGREEMENT.
                DO NOT SIGN OR USE TO AUTHORIZE DISBURSEMENT.
            </div>
        @elseif ($agreement->status !== 'prepared' && $agreement->status !== 'signed')
            <div class="warning">
                DRAFT COPY — NOT READY FOR SIGNATURE.
            </div>
        @endif

        <div class="section-title">1. Agreement and application details</div>

        <div class="details">

            <div>
                <span class="field-label">Agreement number</span>
                <div class="field-value">{{ $agreement->agreement_number }}</div>
            </div>

            <div>
                <span class="field-label">Application number</span>
                <div class="field-value">{{ $application->application_number }}</div>
            </div>

            <div>
                <span class="field-label">Beneficiary</span>
                <div class="field-value">
                    {{ $application->beneficiary?->full_name ?? 'Not available' }}
                </div>
            </div>

            <div class="amount">
                <span class="field-label">Approved amount</span>
                <div class="field-value">
                    PKR {{ \App\Domain\Finance\Support\Money::format($agreement->approved_amount) }}
                </div>
            </div>

            <div>
                <span class="field-label">Agreement date</span>
                <div class="field-value">
                    {{ $agreement->agreement_date?->format('d M Y') ?? 'Not specified' }}
                </div>
            </div>

            <div>
                <span class="field-label">Effective date</span>
                <div class="field-value">
                    {{ $agreement->effective_date?->format('d M Y') ?? 'Not specified' }}
                </div>
            </div>

        </div>

        <div class="section-title">2. Agreement terms</div>

        <div class="terms">{{ $agreement->terms }}</div>

        <div class="section-title">3. Beneficiary acknowledgment</div>

        <p class="ack">
            By signing below, the beneficiary acknowledges that they have had
            an opportunity to review the terms recorded in this agreement and
            receive a copy of the signed document.
        </p>

        <div class="signature-grid">

            <div class="signature-block">
                <strong>Beneficiary</strong>

                <div class="signature-line">
                    Name:
                    {{ $agreement->beneficiary_signatory_name
                        ?? $application->beneficiary?->full_name
                        ?? '________________________' }}
                </div>

                <div class="signature-meta">
                    Signature: __________________________
                    <br>
                    Date: _______________________________
                </div>
            </div>

            <div class="signature-block">
                <strong>Authorized organization representative</strong>

                <div class="signature-line">
                    Name:
                    {{ $agreement->organization_signatory_name ?? '________________________' }}
                </div>

                <div class="signature-meta">
                    Signature: __________________________
                    <br>
                    Date: _______________________________
                </div>
            </div>

            <div class="signature-block">
                <strong>Witness 1</strong>

                <div class="signature-line">
                    Name:
                    {{ $agreement->witness_one_name ?? '________________________' }}
                </div>

                <div class="signature-meta">
                    Signature: __________________________
                    <br>
                    Date: _______________________________
                </div>
            </div>

            <div class="signature-block">
                <strong>Witness 2</strong>

                <div class="signature-line">
                    Name:
                    {{ $agreement->witness_two_name ?? '________________________' }}
                </div>

                <div class="signature-meta">
                    Signature: __________________________
                    <br>
                    Date: _______________________________
                </div>
            </div>

        </div>

        <div class="footer-note">
            The signed document must be retained as evidence. Agreement signing
            does not itself create a payment record or authorize release of funds;
            the organization's separate approval and financial controls still apply.
        </div>

        <div class="footer-brand">
            Tanzeem Falah Naujawanan Kasso &nbsp;|&nbsp; Amal Daramad Committee
        </div>

    </main>

</body>
</html>