<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceMachineDetail extends Model
{
    protected $table = 'hrm_attandance_machine_detail';

    public $timestamps = false;

    protected $guarded = [];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'attandance_id', 'attendance_id');
    }
}
