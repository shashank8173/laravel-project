<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    protected $table = 'tickets';

    protected $primaryKey = 'TicketID';

    const CREATED_AT = 'CreatedAt';

    const UPDATED_AT = 'UpdatedAt';

    protected $fillable = [
        'CategoryID',
        'Title',
        'Description',
        'Status',
        'Priority',
        'EmployeeID',
        'Rating',
    ];

    protected function casts(): array
    {
        return [
            'CreatedAt' => 'datetime',
            'UpdatedAt' => 'datetime',
            'Rating' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'CategoryID');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'EmployeeID');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class, 'ticket_id', 'TicketID');
    }
}
