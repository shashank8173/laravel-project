<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatGroup extends Model
{
    protected $table = 'hrm_chat_groups';

    protected $primaryKey = 'group_id';

    public $timestamps = false;

    protected $guarded = [];

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'group_id', 'group_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            Employee::class,
            'hrm_chat_group_members',
            'group_id',
            'user_id',
            'group_id',
            'id'
        );
    }
}
