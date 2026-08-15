<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $table = 'hrm_notification';

    public $timestamps = false;

    protected $fillable = [
        'send_to',
        'date',
        'sent_by',
        'title',
        'time',
        'description',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'sent_by');
    }
}
