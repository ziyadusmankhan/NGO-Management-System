<?php

namespace App\Http\Requests\Welfare;

use Illuminate\Foundation\Http\FormRequest;

class TransitionWelfareAssistanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->routeIs('welfare.assistance.resubmit')
            ? 'welfare.assistance.manage' : 'welfare.assistance.approve';

        return $this->user()?->hasPermission($permission) ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:5000'],
            'status' => ['missing'],
            'application_id' => ['missing'],
        ];
    }
}
