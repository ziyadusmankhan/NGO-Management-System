<?php

namespace App\Http\Requests\Business;

use App\Domain\Business\Services\BusinessRules;
use Illuminate\Foundation\Http\FormRequest;

class BusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('business.view') ?? false;
    }

    public function rules(): array
    {
        return [];
    }

    public function validated($key = null, $default = null)
    {
        $action = $this->route('action') ?? $this->route()->getActionMethod();
        $input = $this->all();
        if ($action === 'finalize') {
            $input['support_type'] = $this->route('application')->support_type;
        }

        return data_get(BusinessRules::validate($action, $input), $key, $default);
    }
}
