<?php

namespace App\Http\Requests\Qard;

use App\Domain\Qard\Services\QardRules;
use Illuminate\Foundation\Http\FormRequest;

class QardRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('qard.view') ?? false; }
    public function rules(): array { return QardRules::rules($this->route()->getActionMethod()); }
}
