<?php

namespace App\Http\Requests\Welfare;

use Illuminate\Foundation\Http\FormRequest;

class RecordWelfareAgreementSignatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('welfare.agreements.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        // Keep failed input attached to the form that was actually submitted.
        $this->merge(['signature_party' => $this->route('party')]);
    }

    public function rules(): array
    {
        return [
            'signatory_name' => ['required', 'string', 'max:255'],
            'confirmed' => ['required', 'accepted'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'signatory_name.required' => 'Enter the signatory’s full name.',
            'confirmed.accepted' => 'Confirm that you received the signed document and verified the signature.',
            'confirmed.required' => 'Confirm that you received the signed document and verified the signature.',
            'document.required' => 'Select the signed agreement evidence file.',
            'document.uploaded' => 'The evidence file could not be uploaded. Select a file within the server upload limit and try again.',
            'document.mimes' => 'The evidence must be a PDF, JPG or PNG file.',
            'document.max' => 'The evidence file must be no larger than 10 MB.',
        ];
    }
}
