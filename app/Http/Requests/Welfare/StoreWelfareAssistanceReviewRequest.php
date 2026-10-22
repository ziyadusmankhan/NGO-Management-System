<?php

namespace App\Http\Requests\Welfare;

use App\Domain\Welfare\Assistance\Services\WelfareMonitoringService;
use Illuminate\Foundation\Http\FormRequest;

class StoreWelfareAssistanceReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('welfare.reviews.manage') ?? false;
    }

    public function rules(): array
    {
        return app(WelfareMonitoringService::class)->reviewRules();
    }
}
