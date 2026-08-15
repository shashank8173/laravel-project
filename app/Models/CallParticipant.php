<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallParticipant extends Model
{
    protected $table = 'hrm_call_participants';

    protected $fillable = [
        'call_id',
        'user_id',
        'role',
        'status',
        'joined_at',
        'left_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(CallLog::class, 'call_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'user_id');
    }
}
