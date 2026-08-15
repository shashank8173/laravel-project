<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryManagement extends Model
{
    protected $table = 'hrm_salary_management';

    public $timestamps = false;

    protected $fillable = [
        'emp_id',
        'actual_salary',
        'current_salary',
        'added_date',
        'updated_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'actual_salary' => 'decimal:2',
            'current_salary' => 'decimal:2',
            'added_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }
}
