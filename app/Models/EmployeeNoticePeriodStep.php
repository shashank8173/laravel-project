<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeNoticePeriodStep extends Model
{
    protected $table = 'employee_notice_period_steps';

    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'step_id',
        'status',
        'comment',
        'created_at',
        'update_date',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'update_date' => 'datetime',
            'status' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(NoticePeriodStep::class, 'step_id', 'step_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(NoticePeriodFile::class, 'step_id', 'step_id')
            ->where('employee_id', $this->employee_id);
    }
}
