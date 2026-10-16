<?php

namespace App\Http\Requests\Welfare;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWelfareAssistanceReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()
            && $this->user()->hasPermission(
                'welfare.assistance.manage'
            );
    }

    public function rules(): array
    {
        return [
            'review_date' => [
                'required',
                'date',
            ],

            'outcome' => [
                'required',
                Rule::in([
                    'continued',
                    'follow_up_required',
                    'no_longer_needed',
                ]),
            ],

            'next_review_date' => [
                'nullable',
                'date',
                'after_or_equal:review_date',
                'required_unless:outcome,no_longer_needed',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'next_review_date.required_unless' =>
                'A next review date is required unless assistance is no longer needed.',

            'next_review_date.after_or_equal' =>
                'The next review date must be on or after the review date.',
        ];
    }
}