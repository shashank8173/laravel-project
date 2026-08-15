<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $table = 'hrm_holidays';

    public $timestamps = false;

    protected $fillable = [
        'added_by',
        'year',
        'name',
        'date',
        'no_of_days',
    ];
}
