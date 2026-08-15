<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $table = 'hrm_leave_type';

    public $timestamps = false;

    protected $guarded = [];

    public function leaves(): HasMany
    {
        return $this->hasMany(LeaveApplied::class, 'leave_type_id');
    }
}
