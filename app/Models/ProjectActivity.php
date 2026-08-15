<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectActivity extends Model
{
    protected $table = 'hrm_project_activities';

    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'actor_id',
        'action',
        'meta',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'actor_id');
    }

    public function title(): string
    {
        return match ($this->action) {
            'created' => 'Project Created',
            'updated' => 'Project Updated',
            'assigned' => 'Employee Assigned',
            'reassigned' => 'Employee Reassigned',
            'member_removed' => 'Employee Removed',
            'status_changed' => 'Status Changed',
            'progress_changed' => 'Progress Updated',
            'email_sent' => 'Email Sent',
            'email_failed' => 'Email Failed',
            'daily_note' => 'Daily Work Note',
            'task_created' => 'Task Assigned',
            'task_status' => 'Task Status Updated',
            'task_note' => 'Task Note Added',
            'task_deleted' => 'Task Deleted',
            'deleted' => 'Project Deleted',
            default => ucwords(str_replace('_', ' ', (string) $this->action)),
        };
    }
}
