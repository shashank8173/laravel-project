<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAttendance extends Model
{
    protected $table = 'newuser_attendance';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'clock_in_time' => 'datetime',
            'clock_out_time' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'user_id');
    }
}
