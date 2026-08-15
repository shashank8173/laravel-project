<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeNotification extends Model
{
    protected $table = 'hrm_employee_notifications';

    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'actor_id',
        'type',
        'title',
        'body',
        'link',
        'read_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'actor_id');
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function markRead(): void
    {
        if ($this->read_at === null) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }
}
