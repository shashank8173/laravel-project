<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectAssignmentHistory extends Model
{
    protected $table = 'hrm_project_assignment_history';

    public const TYPE_INITIAL = 'initial_assignment';

    public const TYPE_REASSIGNMENT = 'reassignment';

    public const TYPE_MANAGER = 'manager_assignment';

    public const TYPE_ADMIN = 'admin_assignment';

    protected $fillable = [
        'project_id',
        'employee_id',
        'previous_employee_id',
        'assigned_by',
        'assignment_type',
        'reason',
        'status',
        'email_status',
        'email_error',
        'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function previousEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'previous_employee_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_by');
    }

    public function typeLabel(): string
    {
        return match ($this->assignment_type) {
            self::TYPE_INITIAL => 'Initial Assignment',
            self::TYPE_REASSIGNMENT => 'Reassignment',
            self::TYPE_MANAGER => 'Manager Assignment',
            self::TYPE_ADMIN => 'Admin Assignment',
            default => ucwords(str_replace('_', ' ', (string) $this->assignment_type)),
        };
    }
}
