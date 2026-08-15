<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminLoginLog extends Model
{
    protected $table = 'admin_login_logs';

    public $timestamps = false;

    protected $fillable = [
        'admin_id',
        'email',
        'action',
        'timestamp',
        'ip_address',
        'browser_info',
        'email_status',
    ];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
        ];
    }
}
