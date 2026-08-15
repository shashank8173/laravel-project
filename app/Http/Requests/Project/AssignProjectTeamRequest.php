<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignProjectTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\Project $project */
        $project = $this->route('project');

        return $this->user()?->can('assign', $project) === true;
    }

    public function rules(): array
    {
        return [
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => [
                'integer',
                Rule::exists('hrm_employee', 'id')->where(fn ($q) => $q->where('status', 1)->where('archive_status', 0)),
            ],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
