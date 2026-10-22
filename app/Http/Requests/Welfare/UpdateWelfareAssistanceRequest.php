<?php

namespace App\Http\Requests\Welfare;

use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWelfareAssistanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('welfare.assistance.manage') ?? false;
    }

    public function rules(): array
    {
        $administrative = [
            'application_id' => ['missing'],
            'status' => ['missing'],
            'approved_by' => ['missing'],
            'approved_at' => ['missing'],
            'assistance_number' => ['missing'],
            'next_review_date' => ['nullable', 'date'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];

        if ($this->route('assistance')->application_id !== null) {
            return array_merge(array_fill_keys(WelfareAssistance::INHERITED_FIELDS, ['missing']), $administrative);
        }

        return array_merge([
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

            'assistance_type' => [
                'required',
                Rule::in([
                    'monthly_assistance',
                    'food_ration',
                    'medical_assistance',
                    'education_support',
                    'emergency_assistance',
                    'one_time_support',
                ]),
            ],

            'amount' => [
                'required',
                'numeric',
                'regex:/^\d{1,13}(\.\d{1,2})?$/',
                'min:0.01',
                'max:999999999999.99',
            ],

            'frequency' => [
                'nullable',
                Rule::in([
                    'one_time',
                    'weekly',
                    'monthly',
                    'quarterly',
                    'yearly',
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

            'next_review_date' => [
                'nullable',
                'date',
            ],

            'purpose_notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'internal_notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'has_supporting_document' => [
                'nullable',
                'boolean',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ], $administrative);
    }
}
