<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('finance.funds.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (\App\Domain\Finance\Funds\Models\Fund::CONTROL_FLAGS as $flag) {
            if (! $this->exists($flag)) {
                $this->merge([$flag => false]);
            }
        }
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
                Rule::in(['general_welfare', 'small_business', 'qard', 'education', 'emergency', 'community_development', $this->route('fund')?->purpose]),
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
                'string',
                'numeric',
                'regex:/^\d{1,12}(?:\.\d{1,2})?$/',
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
