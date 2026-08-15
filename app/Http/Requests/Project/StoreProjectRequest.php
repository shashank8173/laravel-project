<?php

namespace App\Http\Requests\Project;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Project::class) === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'project_manager_id' => [
                'required',
                'integer',
                Rule::exists('hrm_employee', 'id')->where(fn ($q) => $q->where('status', 1)->where('archive_status', 0)),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'priority' => ['required', Rule::in(Project::PRIORITIES)],
            'status' => ['required', Rule::in(Project::STATUSES)],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'team_ids' => ['nullable', 'array'],
            'team_ids.*' => [
                'integer',
                Rule::exists('hrm_employee', 'id')->where(fn ($q) => $q->where('status', 1)->where('archive_status', 0)),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'End date cannot be before start date.',
            'project_manager_id.exists' => 'Project manager must be an active employee.',
        ];
    }
}
