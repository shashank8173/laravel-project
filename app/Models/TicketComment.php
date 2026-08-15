<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketComment extends Model
{
    protected $table = 'ticket_comments';

    public $timestamps = false;

    protected $fillable = ['ticket_id', 'comment', 'commented_by', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'commented_by');
    }
}
