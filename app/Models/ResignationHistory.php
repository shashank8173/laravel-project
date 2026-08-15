<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResignationHistory extends Model
{
    protected $table = 'resignation_history';

    public $timestamps = false;

    protected $fillable = [
        'resignation_id',
        'employee_id',
        'status',
        'notice_period_days',
        'changed_by',
        'changed_at',
        'comment',
    ];

    protected function casts(): array
    {
        return ['changed_at' => 'datetime'];
    }

    public function resignation(): BelongsTo
    {
        return $this->belongsTo(Resignation::class, 'resignation_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'changed_by');
    }
}
