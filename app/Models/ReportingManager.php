<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportingManager extends Model
{
    protected $table = 'hrm_reporting_manager';

    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'reporting_manager_id',
        'date',
        'reporting_manager_type',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reporting_manager_id');
    }
}
