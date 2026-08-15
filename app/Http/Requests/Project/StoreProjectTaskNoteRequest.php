<?php

namespace App\Http\Requests\Project;

use App\Models\ProjectDailyNote;
use App\Models\ProjectTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectTaskNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ProjectTask $task */
        $task = $this->route('task');
        $user = $this->user();
        if (! $user || ! $task) {
            return false;
        }

        // Assignee OR managers
        return $user->can('addNote', $task);
    }

    public function rules(): array
    {
        return [
            'note_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'note' => ['required', 'string', 'min:3'],
            'work_status' => ['nullable', Rule::in(ProjectDailyNote::WORK_STATUSES)],
            'update_task_status' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $start = $this->input('start_time');
            $end = $this->input('end_time');
            if ($start && $end) {
                $mins = ProjectDailyNote::minutesBetween($start, $end);
                if ($mins === null || $mins <= 0) {
                    $validator->errors()->add('end_time', 'End time must be after start time.');
                }
            } elseif ($start xor $end) {
                $validator->errors()->add('end_time', 'Provide both start and end time, or leave both empty.');
            }
        });
    }
}
