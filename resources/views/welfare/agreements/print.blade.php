<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $agreement->agreement_number }} - Welfare Agreement</title>

    <style>
        :root {
            --green-900: #0d3b2a;
            --green-700: #14573d;
            --green-500: #2e8b57;
            --green-100: #e6f1ea;
            --green-50: #f4f9f6;
            --ink: #111111;
            --muted: #55605a;
            --line: #cfd9d3;
            --red: #a52b2b;
        }

        @page {
            size: A4;
            margin: 14mm 16mm 16mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: var(--ink);
            font-family: "Helvetica Neue", Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            background: #e9eeeb;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ---------- Screen toolbar ---------- */
        .actions {
            max-width: 850px;
            margin: 22px auto 12px;
            display: flex;
            gap: 10px;
        }

        .actions a,
        .actions button {
            display: inline-block;
            padding: 10px 18px;
            border: 1px solid var(--green-700);
            border-radius: 6px;
            background: var(--green-700);
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .actions a {
            background: #fff;
            color: var(--green-700);
        }

        .actions a:hover,
        .actions button:hover {
            background: var(--green-900);
            border-color: var(--green-900);
            color: #fff;
        }

        .actions a:focus-visible,
        .actions button:focus-visible {
            outline: 3px solid var(--green-500);
            outline-offset: 2px;
        }

        /* ---------- Page sheet ---------- */
        .document {
            position: relative;
            max-width: 850px;
            margin: 0 auto 30px;
            padding: 0 44px 34px;
            background: #fff;
            border: 1px solid var(--line);
            border-top: 10px solid var(--green-700);
            overflow: hidden;
        }

        .watermark {
            position: absolute;
            top: 54%;
            left: 50%;
            width: 440px;
            height: 440px;
            transform: translate(-50%, -50%);
            opacity: .05;
            pointer-events: none;
            z-index: 0;
        }

        .document > *:not(.watermark) {
            position: relative;
            z-index: 1;
        }

        /* ---------- Letterhead ---------- */
        .letterhead {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 24px 0 18px;
            border-bottom: 3px solid var(--ink);
        }

        .letterhead-logo {
            flex: 0 0 auto;
            width: 96px;
            height: 96px;
            object-fit: contain;
        }

        .letterhead-text {
            flex: 1 1 auto;
            min-width: 0;
        }

        .org-name {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 21px;
            font-weight: bold;
            line-height: 1.2;
            color: var(--green-900);
        }

        .org-name-ur {
            margin: 4px 0 0;
            font-size: 16px;
            line-height: 1.4;
            color: var(--ink);
            direction: rtl;
            text-align: left;
            unicode-bidi: embed;
        }

        .org-committee {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 11px;
            letter-spacing: .3px;
        }

        .ref-box {
            flex: 0 0 auto;
            min-width: 170px;
            padding: 10px 14px;
            border: 1.5px solid var(--green-700);
            border-radius: 6px;
            background: var(--green-50);
            text-align: right;
        }

        .ref-box .ref-label {
            display: block;
            color: var(--muted);
            font-size: 10px;
        }

        .ref-box .ref-value {
            display: block;
            margin-top: 2px;
            font-family: "Courier New", Courier, monospace;
            font-size: 14px;
            font-weight: bold;
            color: var(--green-900);
            overflow-wrap: anywhere;
        }

        /* ---------- Title ---------- */
        .title-block {
            margin: 26px 0 22px;
            text-align: center;
        }

        h1 {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 1px;
            color: var(--ink);
        }

        .title-rule {
            width: 70px;
            height: 4px;
            margin: 10px auto 0;
            border-radius: 2px;
            background: var(--green-500);
        }

        /* ---------- Warnings ---------- */
        .warning {
            margin-bottom: 22px;
            padding: 12px 14px;
            border: 2px solid var(--red);
            border-left-width: 8px;
            border-radius: 4px;
            background: #fdf3f3;
            color: var(--red);
            text-align: center;
            font-weight: bold;
        }

        /* ---------- Sections ---------- */
        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 26px 0 12px;
            padding: 7px 12px;
            border-left: 5px solid var(--green-700);
            background: var(--green-100);
            font-family: Georgia, "Times New Roman", serif;
            font-size: 14px;
            font-weight: bold;
            color: var(--green-900);
        }

        .details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border: 1px solid var(--line);
            border-radius: 6px;
            overflow: hidden;
        }

        .details > div {
            padding: 10px 14px;
            border-bottom: 1px solid var(--line);
        }

        .details > div:nth-child(odd) {
            border-right: 1px solid var(--line);
        }

        .details > div:nth-last-child(-n+2) {
            border-bottom: none;
        }

        .details > div.amount {
            background: var(--green-700);
            color: #fff;
        }

        .details > div.amount .field-label {
            color: #cfe6d8;
        }

        .field-label {
            display: block;
            margin-bottom: 2px;
            color: var(--muted);
            font-size: 10.5px;
            font-weight: 600;
        }

        .field-value {
            font-size: 13px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .amount .field-value {
            font-size: 15px;
        }

        .terms {
            min-height: 90px;
            padding: 14px 16px;
            border: 1px solid var(--line);
            border-left: 4px solid var(--green-700);
            border-radius: 0 6px 6px 0;
            background: var(--green-50);
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        /* ---------- Signatures ---------- */
        .ack {
            margin: 0 0 4px;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px 22px;
            margin-top: 16px;
        }

        .signature-block {
            min-height: 125px;
            padding: 12px 14px 10px;
            border: 1px solid var(--line);
            border-top: 4px solid var(--ink);
            border-radius: 0 0 6px 6px;
            break-inside: avoid;
        }

        .signature-block > strong {
            font-family: Georgia, "Times New Roman", serif;
            font-size: 12.5px;
            color: var(--green-900);
        }

        .signature-line {
            margin-top: 38px;
            padding-top: 5px;
            border-top: 1px solid var(--ink);
            font-weight: 600;
        }

        .signature-meta {
            margin-top: 8px;
            color: var(--muted);
            font-size: 10.5px;
            line-height: 1.9;
        }

        /* ---------- Footer ---------- */
        .footer-note {
            margin-top: 28px;
            padding: 12px 14px;
            border-top: 3px solid var(--green-700);
            background: var(--green-50);
            color: var(--muted);
            font-size: 10.5px;
        }

        .footer-brand {
            margin-top: 10px;
            text-align: center;
            color: var(--green-900);
            font-size: 10px;
            font-weight: 600;
        }

        /* ---------- Print ---------- */
        @media print {
            body {
                background: #fff;
                font-size: 11px;
            }

            .actions {
                display: none !important;
            }

            .document {
                max-width: none;
                margin: 0;
                padding: 0;
                border: none;
                border-top: none;
                overflow: visible;
            }

            .watermark {
                position: fixed;
            }

            .letterhead {
                padding-top: 0;
            }

            .warning,
            .section-title,
            .details > div.amount,
            .terms,
            .footer-note {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .section-title,
            .signature-block,
            .footer-note {
                break-inside: avoid;
            }

            .section-title {
                break-after: avoid;
            }
        }

        /* ---------- Small screens ---------- */
        @media screen and (max-width: 600px) {
            .document {
                margin: 12px;
                padding: 0 20px 24px;
            }

            .actions {
                margin: 12px;
            }

            .letterhead {
                flex-wrap: wrap;
            }

            .ref-box {
                width: 100%;
                text-align: left;
            }

            .details,
            .signature-grid {
                grid-template-columns: 1fr;
            }

            .details > div:nth-child(odd) {
                border-right: none;
            }

            .details > div:nth-last-child(-n+2) {
                border-bottom: 1px solid var(--line);
            }

            .details > div:last-child {
                border-bottom: none;
            }
        }
    </style>
</head>

<body>

    <div class="actions">
        <button type="button" onclick="window.print()">
            Print / Save as PDF
        </button>

        <a href="{{ route('welfare.applications.agreement.show', $application) }}">
            Return to Agreement
        </a>
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