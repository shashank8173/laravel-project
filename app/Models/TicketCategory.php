<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketCategory extends Model
{
    protected $table = 'hrm_ticket_categories';

    public $timestamps = false;

    protected $fillable = ['name', 'status'];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'CategoryID');
    }
}
