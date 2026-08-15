<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Project extends Model
{
    protected $table = 'hrm_projects';

    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    public const STATUSES = [
        'planning',
        'not_started',
        'in_progress',
        'on_hold',
        'completed',
        'cancelled',
    ];

    protected $fillable = [
        'name',
        'description',
        'client_name',
        'project_manager_id',
        'start_date',
        'end_date',
        'priority',
        'status',
        'progress',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'progress' => 'integer',
        ];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'project_manager_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'hrm_project_employee', 'project_id', 'employee_id')
            ->withPivot(['id', 'assigned_by', 'assigned_at', 'status'])
            ->withTimestamps();
    }

    public function activeMembers(): BelongsToMany
    {
        return $this->members()->wherePivot('status', 'active');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(ProjectEmployee::class, 'project_id');
    }

    public function activeMemberships(): HasMany
    {
        return $this->hasMany(ProjectEmployee::class, 'project_id')->where('status', 'active');
    }

    public function assignmentHistory(): HasMany
    {
        return $this->hasMany(ProjectAssignmentHistory::class, 'project_id')->orderByDesc('assigned_at');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ProjectActivity::class, 'project_id')->orderByDesc('created_at');
    }

    public function dailyNotes(): HasMany
    {
        return $this->hasMany(ProjectDailyNote::class, 'project_id')->orderByDesc('note_date')->orderByDesc('id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'project_id')->orderByDesc('id');
    }

    public function isOverdue(): bool
    {
        if (in_array($this->status, ['completed', 'cancelled'], true)) {
            return false;
        }

        if (! $this->end_date) {
            return false;
        }

        return $this->end_date->lt(Carbon::today());
    }

    public function priorityLabel(): string
    {
        return ucfirst(str_replace('_', ' ', (string) $this->priority));
    }

    public function statusLabel(): string
    {
        return ucwords(str_replace('_', ' ', (string) $this->status));
    }

    public function hasActiveMember(int $employeeId): bool
    {
        return $this->activeMemberships()->where('employee_id', $employeeId)->exists();
    }

    public function scopeVisibleTo($query, Employee $user)
    {
        if ($user->isAdmin() || $user->isHrDepartment()) {
            return $query;
        }

        $teamIds = $user->isReportingManager() ? $user->teamEmployeeIds() : [];

        return $query->where(function ($q) use ($user, $teamIds) {
            $q->where('project_manager_id', $user->id)
                ->orWhere('created_by', $user->id)
                ->orWhereHas('activeMemberships', fn ($m) => $m->where('employee_id', $user->id));

            if ($teamIds !== []) {
                $q->orWhereHas('activeMemberships', fn ($m) => $m->whereIn('employee_id', $teamIds));
            }
        });
    }
}
