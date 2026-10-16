<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fund_id' => [
                'required',
                'integer',
                'exists:funds,id',
            ],

            'category' => [
                'required',
                Rule::in([
                    'welfare',
                    'project',
                    'admin',
                    'education',
                    'medical',
                    'other',
                ]),
            ],

            'description' => [
                'required',
                'string',
                'max:255',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'expense_date' => [
                'required',
                'date',
            ],

            'payee_name' => [
                'required',
                'string',
                'max:200',
            ],

            'payment_method' => [
                'required',
                Rule::in([
                    'cash',
                    'bank',
                    'mobile',
                    'vendor',
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
                'max:100',
            ],

            'status' => [
                'required',
                Rule::in([
                    'pending',
                    'approved',
                    'rejected',
                    'paid',
                ]),
            ],

            'approved_by' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'approved_at' => [
                'nullable',
                'date',
            ],

            'purpose_notes' => [
                'nullable',
                'string',
            ],

            'internal_notes' => [
                'nullable',
                'string',
            ],

            'has_supporting_document' => [
                'boolean',
            ],
        ];
    }
}