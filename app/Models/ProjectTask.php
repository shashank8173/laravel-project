<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ProjectTask extends Model
{
    protected $table = 'hrm_project_tasks';

    public const STATUSES = [
        'pending',
        'working',
        'in_progress',
        'completed',
        'cancelled',
    ];

    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'assigned_to',
        'assigned_by',
        'due_date',
        'priority',
        'status',
        'progress',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'progress' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_by');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ProjectTaskNote::class, 'task_id')->orderByDesc('note_date')->orderByDesc('id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'working' => 'Working',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => ucwords(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function statusClass(): string
    {
        return match ($this->status) {
            'pending' => 'st-not_started',
            'working' => 'st-planning',
            'in_progress' => 'st-in_progress',
            'completed' => 'st-completed',
            'cancelled' => 'st-cancelled',
            default => 'st-not_started',
        };
    }

    public function priorityLabel(): string
    {
        return ucfirst((string) $this->priority);
    }

    public function priorityClass(): string
    {
        return 'pr-'.(string) $this->priority;
    }

    public function isOverdue(): bool
    {
        if (! $this->due_date || in_array($this->status, ['completed', 'cancelled'], true)) {
            return false;
        }

        return $this->due_date->lt(Carbon::today());
    }

    /** Completed / cancelled tasks are read-only for the assignee. */
    public function isLockedForEmployee(): bool
    {
        return in_array($this->status, ['completed', 'cancelled'], true);
    }

    /**
     * Visual flow steps for UI.
     *
     * @return list<array{key:string,label:string,done:bool,current:bool}>
     */
    public function flowSteps(): array
    {
        $order = ['pending', 'working', 'in_progress', 'completed'];
        $current = $this->status === 'cancelled' ? 'cancelled' : $this->status;
        $currentIdx = array_search($current, $order, true);
        if ($currentIdx === false) {
            $currentIdx = -1;
        }

        $steps = [];
        foreach ($order as $i => $key) {
            $steps[] = [
                'key' => $key,
                'label' => match ($key) {
                    'pending' => 'Assigned',
                    'working' => 'Working',
                    'in_progress' => 'In Progress',
                    'completed' => 'Completed',
                    default => ucfirst($key),
                },
                'done' => $currentIdx > $i || ($current === 'completed' && $key === 'completed'),
                'current' => $current === $key,
            ];
        }

        if ($this->status === 'cancelled') {
            $steps[] = [
                'key' => 'cancelled',
                'label' => 'Cancelled',
                'done' => true,
                'current' => true,
            ];
        }

        return $steps;
    }
}
