<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReassignProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\Project $project */
        $project = $this->route('project');
        $user = $this->user();

        if (! $user || ! $user->can('reassign', $project)) {
            return false;
        }

        // Employees may only reassign their own active membership unless admin/PM.
        if ($user->isAdmin() || (int) $project->project_manager_id === (int) $user->id) {
            return true;
        }

        $fromId = (int) $this->input('from_employee_id', $user->id);

        return $fromId === (int) $user->id && $project->hasActiveMember((int) $user->id);
    }

    public function rules(): array
    {
        $user = $this->user();
        $fromId = (int) $this->input('from_employee_id', $user?->id);

        return [
            'from_employee_id' => [
                'nullable',
                'integer',
                Rule::exists('hrm_project_employee', 'employee_id')->where(function ($q) {
                    $q->where('project_id', $this->route('project')->id)
                        ->where('status', 'active');
                }),
            ],
            'to_employee_id' => [
                'required',
                'integer',
                'different:from_employee_id',
                Rule::exists('hrm_employee', 'id')->where(fn ($q) => $q->where('status', 1)->where('archive_status', 0)),
                function ($attribute, $value, $fail) use ($fromId) {
                    if ((int) $value === $fromId) {
                        $fail('Target employee cannot be the current employee.');
                    }
                },
            ],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('from_employee_id') && $this->user()) {
            $this->merge(['from_employee_id' => $this->user()->id]);
        }
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Reason is required for reassignment.',
            'to_employee_id.different' => 'Target employee cannot be the current employee.',
        ];
    }
}
