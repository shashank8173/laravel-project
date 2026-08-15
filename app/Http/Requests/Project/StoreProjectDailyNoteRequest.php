<?php

namespace App\Http\Requests\Project;

use App\Models\ProjectDailyNote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectDailyNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\Project $project */
        $project = $this->route('project');
        $user = $this->user();

        if (! $user || ! $user->can('view', $project)) {
            return false;
        }

        if ($user->isAdmin() || (int) $project->project_manager_id === (int) $user->id) {
            return true;
        }

        return $project->hasActiveMember((int) $user->id);
    }

    public function rules(): array
    {
        return [
            'note_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'note' => ['required', 'string', 'min:3'],
            'work_status' => ['required', Rule::in(ProjectDailyNote::WORK_STATUSES)],
            'progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $start = $this->input('start_time');
            $end = $this->input('end_time');
            if (! $start || ! $end) {
                return;
            }
            $mins = ProjectDailyNote::minutesBetween($start, $end);
            if ($mins === null) {
                $validator->errors()->add('end_time', 'Invalid start/end time.');

                return;
            }
            if ($mins <= 0) {
                $validator->errors()->add('end_time', 'End time must be after start time.');
            }
            if ($mins > 24 * 60) {
                $validator->errors()->add('end_time', 'Duration cannot exceed 24 hours.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'note.required' => 'Please write what you did today.',
            'note.min' => 'Daily note is too short.',
            'start_time.required' => 'Please enter when you started.',
            'end_time.required' => 'Please enter when you finished.',
        ];
    }
}
