<?php

namespace App\Http\Requests\Members;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $memberId =
            $this->route('member')?->id;

        return [
            'full_name' => [
                'required',
                'string',
                'max:200',
            ],

            'father_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'cnic' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('members', 'cnic')
                    ->ignore($memberId),
            ],

            'phone' => [
                'required',
                'string',
                'max:30',
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'joining_date' => [
                'nullable',
                'date',
            ],

            'status' => [
                'required',
                'in:active,inactive,suspended,resigned',
            ],

            'committee_id' => [
                'nullable',
                'exists:committees,id',
            ],

            'committee_position' => [
                'nullable',
                'string',
                'max:100',
            ],

            'committee_joined_at' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' =>
                'The member name is required.',

            'phone.required' =>
                'The phone number is required.',

            'cnic.unique' =>
                'This CNIC is already registered.',

            'email.email' =>
                'Please enter a valid email address.',
        ];
    }
}