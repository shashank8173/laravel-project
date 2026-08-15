<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConnectorSyncLog extends Model
{
    protected $table = 'hrm_connector_sync_logs';

    protected $fillable = [
        'connector_id',
        'direction',
        'status',
        'created_count',
        'updated_count',
        'failed_count',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
        ];
    }

    public function connector(): BelongsTo
    {
        return $this->belongsTo(Connector::class, 'connector_id');
    }
}
