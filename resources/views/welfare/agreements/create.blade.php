@extends('layouts.app')

@section('title', 'Prepare Welfare Agreement')

@section('content')
<div style="max-width:900px;margin:0 auto;padding:24px 0;">

    <div style="margin-bottom:24px;">
        <h1 style="margin:0 0 8px;">Prepare Welfare Agreement</h1>
        <p style="color:#6d7571;margin:0;">
            Prepare a draft for an application that has received final approval.
        </p>
    </div>

    @if ($errors->any())
        <div style="padding:14px;margin-bottom:18px;border:1px solid #e5b5b5;border-radius:8px;background:#fff5f5;color:#9b2525;">
            <strong>Please correct the following:</strong>
            <ul style="margin:8px 0 0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="background:#fff;border:1px solid #e2e7e4;border-radius:12px;padding:24px;margin-bottom:20px;">
        <h2 style="font-size:18px;margin:0 0 18px;">Application summary</h2>

        <p>
            <strong>Application:</strong>
            {{ $application->application_number }}
        </p>

        <p>
            <strong>Beneficiary:</strong>
            {{ $application->beneficiary->full_name }}
        </p>

        <p>
            <strong>Final approved amount:</strong>
            PKR {{ number_format((float) $application->recommended_amount, 2) }}
        </p>

        <p style="margin-bottom:0;">
            <strong>Agreement status:</strong> Draft — not signed
        </p>
    </div>

    <form
        method="POST"
        action="{{ route('welfare.applications.agreement.store', $application) }}"
        style="background:#fff;border:1px solid #e2e7e4;border-radius:12px;padding:24px;"
    >
        @csrf

        <div style="margin-bottom:18px;">
            <label for="agreement_date" style="display:block;font-weight:600;margin-bottom:7px;">
                Agreement date
            </label>

            <input
                type="date"
                id="agreement_date"
                name="agreement_date"
                value="{{ old('agreement_date') }}"
                style="width:100%;padding:11px;border:1px solid #ccd5d0;border-radius:7px;"
            >

            @error('agreement_date')
                <p style="color:#b42318;">{{ $message }}</p>
            @enderror
        </div>

        <div style="margin-bottom:18px;">
            <label for="effective_date" style="display:block;font-weight:600;margin-bottom:7px;">
                Effective date
            </label>

            <input
                type="date"
                id="effective_date"
                name="effective_date"
                value="{{ old('effective_date') }}"
                style="width:100%;padding:11px;border:1px solid #ccd5d0;border-radius:7px;"
            >

            @error('effective_date')
                <p style="color:#b42318;">{{ $message }}</p>
            @enderror
        </div>

        <div style="margin-bottom:18px;">
            <label for="terms" style="display:block;font-weight:600;margin-bottom:7px;">
                Agreement terms
            </label>

            <textarea
                id="terms"
                name="terms"
                rows="9"
                maxlength="20000"
                required
                placeholder="Describe the approved support, purpose, responsibilities, conditions, timeline, and any applicable recovery or monitoring arrangements."
                style="width:100%;padding:12px;border:1px solid #ccd5d0;border-radius:7px;"
            >{{ old('terms') }}</textarea>

            @error('terms')
                <p style="color:#b42318;">{{ $message }}</p>
            @enderror
        </div>

        <div style="margin-bottom:24px;">
            <label for="notes" style="display:block;font-weight:600;margin-bottom:7px;">
                Internal notes (optional)
            </label>

            <textarea
                id="notes"
                name="notes"
                rows="3"
                maxlength="5000"
                style="width:100%;padding:12px;border:1px solid #ccd5d0;border-radius:7px;"
            >{{ old('notes') }}</textarea>

            @error('notes')
                <p style="color:#b42318;">{{ $message }}</p>
            @enderror
        </div>

        <p style="color:#6d7571;font-size:14px;line-height:1.6;">
            Saving this form creates a draft agreement only. It does not record signatures,
            create welfare assistance, or generate a financial transaction.
        </p>

        <button
            type="submit"
            style="padding:12px 20px;border:0;border-radius:8px;background:#176b4d;color:white;font-weight:600;cursor:pointer;"
        >
            Save Draft Agreement
        </button>

        <a
            href="{{ route('welfare.applications.show', $application) }}"
            style="display:inline-block;margin-left:12px;color:#44534b;text-decoration:none;"
        >
            Cancel
        </a>
    </form>
</div>
@endsection
