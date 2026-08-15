<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveApplied extends Model
{
    protected $table = 'hrm_leave_applied';

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected $fillable = [
        'leave_type_id',
        'start_date',
        'end_date',
        'no_of_days',
        'leave_reason',
        'day_type',
        'emp_id',
        'approved_by',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'created_at' => 'datetime',
            'status' => 'integer',
            'no_of_days' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    public function statusLabel(): string
    {
        return match ((int) $this->status) {
            0 => 'New',
            1 => 'Pending',
            2 => 'Approved',
            3 => 'Rejected',
            default => 'Unknown',
        };
    }

    public function actionByLabel(): ?string
    {
        if (! $this->approved_by || (int) $this->approved_by === 0) {
            return null;
        }

        $name = $this->approver?->full_name ?: $this->approver?->fname;
        if (! $name) {
            return null;
        }

        return match ((int) $this->status) {
            1 => 'Pending by '.$name,
            2 => 'Approved by '.$name,
            3 => 'Rejected by '.$name,
            default => 'By '.$name,
        };
    }
}
