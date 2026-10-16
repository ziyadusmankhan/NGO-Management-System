<?php

namespace App\Http\Requests\Welfare;

use Illuminate\Foundation\Http\FormRequest;

class VoidWelfareAssistancePaymentRequest extends FormRequest
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
            'void_reason' => [
                'required',
                'string',
                'min:5',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'void_reason.required' =>
                'Please provide a reason for voiding this payment.',

            'void_reason.min' =>
                'The void reason must contain at least 5 characters.',
        ];
    }
}