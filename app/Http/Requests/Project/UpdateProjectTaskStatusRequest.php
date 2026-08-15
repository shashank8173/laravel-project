<?php

namespace App\Http\Requests\Project;

use App\Models\ProjectDailyNote;
use App\Models\ProjectTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ProjectTask $task */
        $task = $this->route('task');

        return $this->user()?->can('updateStatus', $task) === true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(ProjectTask::STATUSES)],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }
}
