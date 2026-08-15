<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resignation extends Model
{
    protected $table = 'employee_resignations';

    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'resignation_reason',
        'intended_last_date',
        'notice_period_days',
        'submitted_at',
        'status',
        'approved_by',
        'approved_at',
        'updated_at',
        'decline_reason',
    ];

    protected function casts(): array
    {
        return [
            'intended_last_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    public function history(): HasMany
    {
        return $this->hasMany(ResignationHistory::class, 'resignation_id');
    }

    public function employeeNoticeSteps(): HasMany
    {
        return $this->hasMany(EmployeeNoticePeriodStep::class, 'employee_id', 'employee_id');
    }
}
