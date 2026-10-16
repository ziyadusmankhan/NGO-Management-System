<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDonationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'donor_type' => [
                'required',
                Rule::in([
                    'individual',
                    'family',
                    'organization',
                ]),
            ],

            'donor_name' => [
                'required',
                'string',
                'max:200',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'donation_date' => [
                'required',
                'date',
            ],

            'payment_method' => [
                'required',
                Rule::in([
                    'cash',
                    'bank',
                    'mobile',
                    'online',
                ]),
            ],

            'transaction_reference' => [
                'nullable',
                'string',
                'max:150',
            ],

            'receipt_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('donations', 'receipt_number')
                    ->ignore($this->route('donation')),
            ],

            'designation' => [
                'required',
                Rule::in([
                    'unrestricted',
                    'restricted',
                ]),
            ],

            'purpose' => [
                'required',
                Rule::in([
                    'general',
                    'business',
                    'qard',
                    'education',
                    'emergency',
                    'project',
                ]),
            ],

            'fund_id' => [
                'nullable',
                'exists:funds,id',
            ],

            'purpose_notes' => [
                'nullable',
                'string',
            ],

            'internal_notes' => [
                'nullable',
                'string',
            ],

            'status' => [
                'required',
                Rule::in([
                    'pending',
                    'received',
                    'refunded',
                    'cancelled',
                ]),
            ],
        ];
    }
}
