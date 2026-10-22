<?php

namespace App\Http\Requests\Welfare;

use Illuminate\Foundation\Http\FormRequest;

class StoreWelfareAssistancePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(
            'welfare.assistance.manage'
        ) ?? false;
    }

    public function rules(): array
    {
        return [

            'idempotency_key' => [
                'required',
                'string',
                'max:64',
            ],

            'amount' => [
                'required',
                'string',
                'numeric',
                'regex:/^\d{1,12}(?:\.\d{1,2})?$/',
                'min:0.01',
                'max:999999999999.99',
            ],

            'payment_date' => [
                'required',
                'date',
            ],

            'payment_method' => [
                'required',
                'in:cash,bank_transfer,mobile_wallet,cheque',
            ],

            'transaction_reference' => [
                'nullable',
                'string',
                'max:255',
            ],

            'receipt_number' => [
                'nullable',
                'string',
                'max:255',
            ],

            'paid_to' => [
                'nullable',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'fund_id' => ['missing'],
            'assistance_id' => ['missing'],
            'status' => ['missing'],
            'recorded_by' => ['missing'],
            'payment_number' => ['missing'],

        ];
    }

    public function messages(): array
    {
        return [
            'amount.string' => 'Send the amount as decimal text, for example "10.25".',
            'amount.regex' => 'Enter a positive amount with at most two decimal places (maximum 999999999999.99).',
        ];
    }
}
