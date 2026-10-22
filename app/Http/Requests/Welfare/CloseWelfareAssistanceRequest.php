<?php

namespace App\Http\Requests\Welfare;

use Illuminate\Foundation\Http\FormRequest;

class CloseWelfareAssistanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('welfare.reviews.close') ?? false;
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:5000']];
    }
}
