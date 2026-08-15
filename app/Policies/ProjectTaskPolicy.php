<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectTask;

class ProjectTaskPolicy
{
    public function view(Employee $user, ProjectTask $task): bool
    {
        $project = $task->relationLoaded('project') ? $task->project : Project::query()->find($task->project_id);

        return $project && $user->can('view', $project);
    }

    public function update(Employee $user, ProjectTask $task): bool
    {
        $project = $task->relationLoaded('project') ? $task->project : Project::query()->find($task->project_id);
        if (! $project) {
            return false;
        }

        // Managers can always edit (including reopen completed)
        if ($user->can('manageTasks', $project)) {
            return true;
        }

        // Assignee: only before completion
        if ((int) $task->assigned_to !== (int) $user->id) {
            return false;
        }

        return ! $task->isLockedForEmployee();
    }

    public function updateStatus(Employee $user, ProjectTask $task): bool
    {
        return $this->update($user, $task);
    }

    public function addNote(Employee $user, ProjectTask $task): bool
    {
        return $this->update($user, $task);
    }

    public function delete(Employee $user, ProjectTask $task): bool
    {
        $project = $task->relationLoaded('project') ? $task->project : Project::query()->find($task->project_id);

        return $project && $user->can('manageTasks', $project);
    }
}
