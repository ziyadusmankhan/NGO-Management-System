<?php

namespace App\Http\Requests\Welfare;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWelfareApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()
            ?->hasPermission('welfare.assistance.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'beneficiary_id' => [
                'required',
                'integer',
                'exists:beneficiaries,id',
            ],

            'fund_id' => [
                'nullable',
                'integer',
                'exists:funds,id',
            ],

            'category' => [
                'required',
                Rule::in([
                    'welfare',
                    'livelihood',
                    'education',
                    'emergency',
                ]),
            ],

            'request_type' => [
                'required',
                'string',
                'max:60',
            ],

            'requested_amount' => [
                'required',
                'string',
                'regex:/^\d{1,12}(?:\.\d{1,2})?$/',
                'numeric',
                'min:0.01',
                'max:999999999999.99',
            ],

            'applicant_contribution' => [
                'nullable',
                'string',
                'regex:/^\d{1,12}(?:\.\d{1,2})?$/',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'organization_contribution' => [
                'nullable',
                'string',
                'regex:/^\d{1,12}(?:\.\d{1,2})?$/',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'purpose_details' => [
                'required',
                'string',
                'min:10',
                'max:10000',
            ],

            'priority' => [
                'required',
                Rule::in([
                    'high',
                    'normal',
                    'low',
                ]),
            ],

            'frequency' => [
                'required',
                Rule::in([
                    'one_time',
                    'weekly',
                    'monthly',
                    'quarterly',
                ]),
            ],

            'start_date' => [
                'nullable',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'has_supporting_document' => [
                'nullable',
                'boolean',
            ],

            'internal_notes' => [
                'nullable',
                'string',
                'max:10000',
            ],
            'supporting_documents' => [
                'nullable',
                'array',
                'max:10',
            ],

            'supporting_documents.*' => [
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:10240',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'beneficiary_id.exists' =>
                'The selected beneficiary does not exist.',

            'fund_id.exists' =>
                'The selected funding source does not exist.',

            'requested_amount.min' =>
                'The requested amount must be greater than zero.',

            'end_date.after_or_equal' =>
                'The end date must be on or after the start date.',

            'purpose_details.min' =>
                'Please provide enough detail about the reason for the request.',
        ];
    }
}