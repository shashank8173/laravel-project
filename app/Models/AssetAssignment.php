<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetAssignment extends Model
{
    protected $table = 'hrm_asset_assignments';

    public $timestamps = false;

    protected $fillable = [
        'asset_id',
        'assigned_date',
        'assignee_id',
        'action',
        'issued_date',
        'return_date',
    ];

    protected function casts(): array
    {
        return [
            'assigned_date' => 'date',
            'issued_date' => 'date',
            'return_date' => 'date',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assignee_id');
    }
}
