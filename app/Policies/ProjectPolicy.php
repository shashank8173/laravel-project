<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectTask;

class ProjectPolicy
{
    public function viewAny(Employee $user): bool
    {
        return true;
    }

    public function view(Employee $user, Project $project): bool
    {
        if ($user->isAdmin() || $user->isHrDepartment()) {
            return true;
        }

        if ((int) $project->project_manager_id === (int) $user->id) {
            return true;
        }

        if ((int) $project->created_by === (int) $user->id) {
            return true;
        }

        if ($project->hasActiveMember((int) $user->id)) {
            return true;
        }

        // Reporting managers can see projects where their reports are assigned
        if ($user->isReportingManager()) {
            $teamIds = $user->teamEmployeeIds();
            if ($teamIds !== [] && $project->activeMemberships()->whereIn('employee_id', $teamIds)->exists()) {
                return true;
            }
        }

        return false;
    }

    public function create(Employee $user): bool
    {
        return $user->isAdmin() || $user->isHrDepartment();
    }

    public function update(Employee $user, Project $project): bool
    {
        if ($user->isAdmin() || $user->isHrDepartment()) {
            return true;
        }

        return (int) $project->project_manager_id === (int) $user->id;
    }

    public function delete(Employee $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    public function assign(Employee $user, Project $project): bool
    {
        return $this->update($user, $project) || $user->isReportingManager();
    }

    public function reassign(Employee $user, Project $project): bool
    {
        if ($this->assign($user, $project)) {
            return true;
        }

        return $project->hasActiveMember((int) $user->id);
    }

    public function updateStatus(Employee $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    public function updateProgress(Employee $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    public function viewActivity(Employee $user, Project $project): bool
    {
        return $this->view($user, $project);
    }

    public function addDailyNote(Employee $user, Project $project): bool
    {
        if ($this->manageTasks($user, $project)) {
            return true;
        }

        return $project->hasActiveMember((int) $user->id);
    }

    /** Super admin / admin / HR / manager / project manager can create & assign tasks */
    public function manageTasks(Employee $user, Project $project): bool
    {
        if ($user->isAdmin() || $user->isHrDepartment() || $user->isReportingManager()) {
            return true;
        }

        return (int) $project->project_manager_id === (int) $user->id;
    }

    public function updateTask(Employee $user, ProjectTask $task): bool
    {
        $project = $task->project ?? Project::query()->find($task->project_id);
        if (! $project || ! $this->view($user, $project)) {
            return false;
        }

        // Managers can edit full task
        if ($this->manageTasks($user, $project)) {
            return true;
        }

        // Assignee can only update their own task (status/progress/notes via separate actions)
        return (int) $task->assigned_to === (int) $user->id;
    }

    public function updateOwnTaskStatus(Employee $user, ProjectTask $task): bool
    {
        return (int) $task->assigned_to === (int) $user->id
            || $this->manageTasks($user, $task->project ?? Project::query()->find($task->project_id));
    }
}
