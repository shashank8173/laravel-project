<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvanceSalary extends Model
{
    protected $table = 'hrm_advance_salary';

    public $timestamps = false;

    protected $fillable = [
        'emp_id',
        'advance_amount',
        'monthly_deduction',
        'advance_date',
        'status',
        'added_date',
        'remaining_amount',
    ];

    protected function casts(): array
    {
        return [
            'advance_amount' => 'decimal:2',
            'monthly_deduction' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'advance_date' => 'datetime',
            'added_date' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }
}
