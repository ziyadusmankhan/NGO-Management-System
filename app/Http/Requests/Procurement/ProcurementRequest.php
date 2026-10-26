<?php

namespace App\Http\Requests\Procurement;

use App\Domain\Procurement\Services\ProcurementRules;
use Illuminate\Foundation\Http\FormRequest;

class ProcurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('procurement.view') ?? false;
    }

    public function rules(): array
    {
        return [];
    }

    public function validated($key = null, $default = null)
    {
        return data_get(ProcurementRules::validate($this->route('action') ?? $this->route()->getActionMethod(), $this->all()), $key, $default);
    }
}
