<?php

namespace App\Http\Requests\Project;

use App\Models\Project;
use App\Models\ProjectTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Project $project */
        $project = $this->route('project');

        return $this->user()?->can('manageTasks', $project) === true;
    }

    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => [
                'required',
                'integer',
                Rule::exists('hrm_employee', 'id')->where(fn ($q) => $q->where('status', 1)->where('archive_status', 0)),
                // Prefer team members, but allow any active employee for admin flexibility
            ],
            'due_date' => ['nullable', 'date'],
            'priority' => ['required', Rule::in(ProjectTask::PRIORITIES)],
            'status' => ['nullable', Rule::in(ProjectTask::STATUSES)],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }
}
