<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $table = 'hrm_chat_messages';

    protected $primaryKey = 'message_id';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'seen' => 'integer',
            'is_deleted' => 'integer',
            'is_edited' => 'integer',
            'reply_to' => 'integer',
            'timestamp' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'receiver_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to', 'message_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ChatGroup::class, 'group_id', 'group_id');
    }
}
