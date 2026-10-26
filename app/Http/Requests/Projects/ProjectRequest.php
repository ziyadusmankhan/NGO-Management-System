<?php

namespace App\Http\Requests\Projects;

use App\Domain\Projects\Services\ProjectRules;
use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('projects.view') ?? false;
    }

    public function rules(): array
    {
        return [];
    }

    public function validated($key = null, $default = null)
    {
        return data_get(ProjectRules::validate($this->route('action') ?? $this->route()->getActionMethod(), $this->all()), $key, $default);
    }
}
