<?php

use App\Models\CallLog;
use App\Models\ChatMessage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hrm_chat_messages')) {
            return;
        }

        DB::statement("ALTER TABLE `hrm_chat_messages` MODIFY `message_type` ENUM('text','image','audio','video','file','call') NOT NULL DEFAULT 'text'");

        if (! Schema::hasTable('hrm_call_logs')) {
            return;
        }

        $calls = CallLog::query()
            ->whereIn('status', ['ended', 'missed', 'rejected', 'failed'])
            ->orderBy('id')
            ->get();

        foreach ($calls as $call) {
            $exists = ChatMessage::query()
                ->where('message_type', 'call')
                ->where('message', 'like', '%"call_id":'.$call->id.'%')
                ->exists();
            if ($exists) {
                continue;
            }

            $label = $call->call_type === 'video' ? 'Video call' : 'Audio call';
            if (($call->scope ?? 'dm') === 'group') {
                $label = $call->call_type === 'video' ? 'Group video call' : 'Group audio call';
            }

            $detail = match ($call->status) {
                'rejected' => 'Declined',
                'missed' => 'Missed',
                'failed' => 'Failed',
                'ended' => ((int) $call->duration_seconds > 0)
                    ? sprintf('%d:%02d', intdiv((int) $call->duration_seconds, 60), ((int) $call->duration_seconds) % 60)
                    : 'Ended',
                default => ucfirst((string) $call->status),
            };

            ChatMessage::query()->create([
                'sender_id' => $call->caller_id,
                'receiver_id' => $call->group_id ? null : $call->receiver_id,
                'group_id' => $call->group_id,
                'message' => json_encode([
                    'call_id' => $call->id,
                    'call_type' => $call->call_type,
                    'scope' => $call->scope ?? 'dm',
                    'status' => $call->status,
                    'duration' => (int) $call->duration_seconds,
                    'label' => $label,
                    'detail' => $detail,
                ]),
                'message_type' => 'call',
                'file_path' => null,
                'reply_to' => null,
                'status' => 'sent',
                'seen' => 1,
                'is_deleted' => 0,
                'is_edited' => 0,
                'timestamp' => $call->ended_at ?: ($call->started_at ?: now()),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('hrm_chat_messages')) {
            return;
        }

        ChatMessage::query()->where('message_type', 'call')->delete();
        DB::statement("ALTER TABLE `hrm_chat_messages` MODIFY `message_type` ENUM('text','image','audio','video','file') NOT NULL DEFAULT 'text'");
    }
};
