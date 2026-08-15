<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallSignal extends Model
{
    protected $table = 'hrm_call_signals';

    protected $fillable = [
        'call_id',
        'sender_id',
        'receiver_id',
        'type',
        'payload',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'consumed_at' => 'datetime',
        ];
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(CallLog::class, 'call_id');
    }
}
