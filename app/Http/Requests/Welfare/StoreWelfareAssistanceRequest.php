<?php

namespace App\Http\Requests\Welfare;

use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWelfareAssistanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('welfare.assistance.manage') ?? false;
    }

    public function rules(): array
    {
        return array_merge(
            array_fill_keys(WelfareAssistance::INHERITED_FIELDS, ['missing']),
            [
                'application_id' => ['required', 'integer', Rule::exists('welfare_applications', 'id')->whereNull('deleted_at')],
                'next_review_date' => ['nullable', 'date'],
                'internal_notes' => ['nullable', 'string', 'max:5000'],
                'notes' => ['nullable', 'string', 'max:5000'],
                'status' => ['missing'],
                'approved_by' => ['missing'],
                'approved_at' => ['missing'],
                'assistance_number' => ['missing'],
            ]
        );
    }
}
