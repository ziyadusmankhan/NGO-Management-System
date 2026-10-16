<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('funds', 'code')
                    ->ignore($this->route('fund')),
            ],

            'type' => [
                'required',
                Rule::in([
                    'general',
                    'restricted',
                    'designated',
                ]),
            ],

            'purpose' => [
                'required',
                'string',
                'max:100',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'usage_rules' => [
                'nullable',
                'string',
            ],

            'responsible_authority' => [
                'nullable',
                'string',
                'max:150',
            ],

            'opening_balance' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'opening_balance_date' => [
                'nullable',
                'date',
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],

            'restrict_spending' => [
                'nullable',
                'boolean',
            ],

            'require_approval' => [
                'nullable',
                'boolean',
            ],

            'require_documents' => [
                'nullable',
                'boolean',
            ],

            'monitor_balance' => [
                'nullable',
                'boolean',
            ],

            'record_transactions' => [
                'nullable',
                'boolean',
            ],

            'prevent_negative_balance' => [
                'nullable',
                'boolean',
            ],

            'maintain_audit_history' => [
                'nullable',
                'boolean',
            ],

            'protect_restricted_designation' => [
                'nullable',
                'boolean',
            ],

            'internal_notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
