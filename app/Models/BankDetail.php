<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankDetail extends Model
{
    protected $table = 'hrm_bank_detail';

    public $timestamps = false;

    protected $fillable = [
        'emp_id',
        'bank_name',
        'account_type',
        'account_holder_name',
        'account_number',
        'ifsc',
        'branch',
        'pan',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }
}
