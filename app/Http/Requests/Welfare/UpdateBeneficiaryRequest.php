<?php

namespace App\Http\Requests\Welfare;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBeneficiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check()
            && auth()->user()->hasPermission('welfare.beneficiaries.manage');
    }

    public function rules(): array
    {
        return [
            'full_name' => [
                'required',
                'string',
                'max:200',
            ],

            'father_husband_name' => [
                'nullable',
                'string',
                'max:200',
            ],

            'cnic' => [
                'nullable',
                'string',
                'max:30',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'alternate_phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'location' => [
                'nullable',
                'string',
                'max:150',
            ],

            'category' => [
                'required',
                Rule::in([
                    'elderly_poor',
                    'disabled_vulnerable',
                    'poor_employable',
                    'student',
                    'emergency',
                ]),
            ],

            'marital_status' => [
                'nullable',
                Rule::in([
                    'single',
                    'married',
                    'widowed',
                    'divorced',
                    'separated',
                ]),
            ],

            'dependents_count' => [
                'required',
                'integer',
                'min:0',
                'max:100',
            ],

            'occupation' => [
                'nullable',
                'string',
                'max:150',
            ],

            'monthly_income' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'assets_description' => [
                'nullable',
                'string',
            ],

            'debts_description' => [
                'nullable',
                'string',
            ],

            'household_condition' => [
                'nullable',
                'string',
            ],

            'next_review_date' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}