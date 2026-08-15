<?php

namespace App\Http\Controllers;

use App\Models\CallLog;
use App\Models\CallParticipant;
use App\Models\CallSignal;
use App\Models\ChatGroup;
use App\Models\ChatMessage;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

class CallController extends Controller
{
    public function config(): JsonResponse
    {
        return response()->json([
            'iceServers' => $this->iceServers(),
            'ringTimeout' => (int) config('webrtc.ring_timeout', 45),
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'call_type' => ['required', 'in:audio,video'],
            'receiver_id' => ['nullable', 'integer'],
            'group_id' => ['nullable', 'integer'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer'],
        ]);

        $me = (int) Auth::id();
        $this->expireStaleCalls();

        if ($this->userInActiveCall($me)) {
            return response()->json(['status' => 'error', 'message' => 'You are already in a call', 'code' => 'busy_self'], 409);
        }

        $groupId = isset($data['group_id']) ? (int) $data['group_id'] : null;
        $receiverId = isset($data['receiver_id']) ? (int) $data['receiver_id'] : null;
        $memberIds = array_values(array_unique(array_map('intval', $data['member_ids'] ?? [])));
        $memberIds = array_values(array_filter($memberIds, fn ($id) => $id > 0 && $id !== $me));

        if ($groupId) {
            if (! $this->isGroupMember($groupId, $me)) {
                return response()->json(['status' => 'error', 'message' => 'Not a member of this group'], 403);
            }
            $allowed = ChatGroup::query()->findOrFail($groupId)
                ->members()->pluck('hrm_employee.id')->map(fn ($id) => (int) $id)->all();
            $memberIds = array_values(array_intersect($memberIds, $allowed));
            if ($memberIds === []) {
                // Default: invite all other group members (capped)
                $memberIds = array_values(array_filter($allowed, fn ($id) => $id !== $me));
            }
            $scope = 'group';
        } else {
            if (! $receiverId && $memberIds === []) {
                return response()->json(['status' => 'error', 'message' => 'Select at least one person to call'], 422);
            }
            if ($receiverId) {
                $memberIds = array_values(array_unique(array_merge([$receiverId], $memberIds)));
            }
            $scope = count($memberIds) > 1 ? 'group' : 'dm';
            $groupId = null;
        }

        if ($memberIds === []) {
            return response()->json(['status' => 'error', 'message' => 'No participants to invite'], 422);
        }

        foreach ($memberIds as $uid) {
            $ok = Employee::query()->where('id', $uid)->where('status', 1)->where('archive_status', 0)->exists();
            if (! $ok) {
                return response()->json(['status' => 'error', 'message' => 'One or more employees are not available'], 404);
            }
            if ($this->userInActiveCall($uid)) {
                // Skip busy users rather than fail whole call for groups; for 1:1 fail
                if ($scope === 'dm') {
                    return response()->json(['status' => 'error', 'message' => 'User is busy', 'code' => 'busy'], 409);
                }
                $memberIds = array_values(array_filter($memberIds, fn ($id) => $id !== $uid));
            }
        }

        if ($memberIds === []) {
            return response()->json(['status' => 'error', 'message' => 'All selected users are busy'], 409);
        }

        $call = CallLog::query()->create([
            'call_uuid' => (string) Str::uuid(),
            'scope' => $scope,
            'caller_id' => $me,
            'receiver_id' => $memberIds[0],
            'group_id' => $groupId,
            'call_type' => $data['call_type'],
            'status' => 'calling',
            'started_at' => now(),
        ]);

        CallParticipant::query()->create([
            'call_id' => $call->id,
            'user_id' => $me,
            'role' => 'host',
            'status' => 'joined',
            'joined_at' => now(),
        ]);

        $callerBrief = $this->employeeBrief(Auth::user());
        foreach ($memberIds as $uid) {
            CallParticipant::query()->create([
                'call_id' => $call->id,
                'user_id' => $uid,
                'role' => 'member',
                'status' => 'invited',
            ]);
            $this->pushSignal($call, $me, $uid, 'invite', [
                'call_uuid' => $call->call_uuid,
                'call_type' => $call->call_type,
                'scope' => $scope,
                'group_id' => $groupId,
                'caller' => $callerBrief,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'call' => $this->serializeCall($call->fresh(['participants.user']), $me),
            'iceServers' => $this->iceServers(),
        ]);
    }

    public function invite(Request $request): JsonResponse
    {
        $data = $request->validate([
            'call_id' => ['required', 'integer'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer'],
        ]);

        $me = (int) Auth::id();
        $call = CallLog::query()->with('participants')->find($data['call_id']);

        if (! $call || ! $this->participantJoined($call, $me)) {
            return response()->json(['status' => 'error', 'message' => 'Call not found'], 404);
        }

        if (! in_array($call->status, CallLog::activeStatuses(), true)) {
            return response()->json(['status' => 'error', 'message' => 'Call already finished'], 409);
        }

        $ids = array_values(array_unique(array_map('intval', $data['user_ids'])));
        $ids = array_values(array_filter($ids, fn ($id) => $id > 0 && $id !== $me));

        // Allow inviting any active employee mid-call (not only original group roster).
        $ids = Employee::query()
            ->whereIn('id', $ids ?: [0])
            ->where('status', 1)
            ->where('archive_status', 0)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $invited = [];
        $callerBrief = $this->employeeBrief(Auth::user());
        foreach ($ids as $uid) {
            if ($this->userInActiveCall($uid)) {
                continue;
            }
            $existing = $call->participants()->where('user_id', $uid)->first();
            if ($existing && in_array($existing->status, ['invited', 'ringing', 'joined'], true)) {
                continue;
            }

            if ($existing) {
                $existing->status = 'invited';
                $existing->left_at = null;
                $existing->joined_at = null;
                $existing->save();
            } else {
                CallParticipant::query()->create([
                    'call_id' => $call->id,
                    'user_id' => $uid,
                    'role' => 'member',
                    'status' => 'invited',
                ]);
            }

            // Upgrade DM call to multi-party room
            if (($call->scope ?? 'dm') === 'dm') {
                $call->scope = 'group';
                $call->save();
            }

            $this->pushSignal($call, $me, $uid, 'invite', [
                'call_uuid' => $call->call_uuid,
                'call_type' => $call->call_type,
                'scope' => $call->scope,
                'group_id' => $call->group_id,
                'caller' => $callerBrief,
            ]);
            $invited[] = $uid;
        }

        if ($invited === []) {
            return response()->json(['status' => 'error', 'message' => 'No users could be invited (busy or invalid)'], 422);
        }

        return response()->json([
            'status' => 'success',
            'invited' => $invited,
            'call' => $this->serializeCall($call->fresh(['participants.user']), $me),
        ]);
    }

    public function accept(Request $request): JsonResponse
    {
        $data = $request->validate(['call_id' => ['required', 'integer']]);
        $me = (int) Auth::id();
        $call = CallLog::query()->with('participants.user')->find($data['call_id']);

        if (! $call) {
            return response()->json(['status' => 'error', 'message' => 'Call not found'], 404);
        }

        $part = $call->participants()->where('user_id', $me)->first();
        if (! $part && (int) $call->receiver_id === $me) {
            $part = CallParticipant::query()->create([
                'call_id' => $call->id,
                'user_id' => $me,
                'role' => 'member',
                'status' => 'invited',
            ]);
        }

        if (! $part || ! in_array($part->status, ['invited', 'ringing'], true)) {
            return response()->json(['status' => 'error', 'message' => 'Call not found'], 404);
        }

        if (! in_array($call->status, ['calling', 'ringing', 'connecting', 'connected'], true)) {
            return response()->json(['status' => 'error', 'message' => 'Call is no longer available'], 409);
        }

        if ($this->userInActiveCall($me, $call->id)) {
            return response()->json(['status' => 'error', 'message' => 'You are already in a call', 'code' => 'busy_self'], 409);
        }

        $part->status = 'joined';
        $part->joined_at = now();
        $part->save();

        if (in_array($call->status, ['calling', 'ringing'], true)) {
            $call->status = 'connecting';
            $call->answered_at = $call->answered_at ?: now();
            $call->save();
        }

        $joinedPeers = $call->participants()
            ->where('status', 'joined')
            ->where('user_id', '!=', $me)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $meBrief = $this->employeeBrief(Auth::user());
        foreach ($joinedPeers as $peerId) {
            $this->pushSignal($call, $me, $peerId, 'peer-joined', [
                'user' => $meBrief,
                'call_uuid' => $call->call_uuid,
            ]);
            // Also notify host with classic "accepted" for 1:1 clients
            $this->pushSignal($call, $me, $peerId, 'accepted', [
                'user' => $meBrief,
                'call_uuid' => $call->call_uuid,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'call' => $this->serializeCall($call->fresh(['participants.user']), $me),
            'peers' => array_map(fn ($id) => $this->employeeBrief(Employee::query()->find($id)), $joinedPeers),
            'iceServers' => $this->iceServers(),
        ]);
    }

    public function reject(Request $request): JsonResponse
    {
        $data = $request->validate(['call_id' => ['required', 'integer']]);
        $me = (int) Auth::id();
        $call = CallLog::query()->find($data['call_id']);

        if (! $call) {
            return response()->json(['status' => 'error', 'message' => 'Call not found'], 404);
        }

        $part = $call->participants()->where('user_id', $me)->first();
        if (! $part && (int) $call->receiver_id !== $me) {
            return response()->json(['status' => 'error', 'message' => 'Call not found'], 404);
        }

        if ($part) {
            $part->status = 'rejected';
            $part->left_at = now();
            $part->save();
        }

        $hostId = (int) $call->caller_id;
        $this->pushSignal($call, $me, $hostId, 'rejected', [
            'call_uuid' => $call->call_uuid,
            'user_id' => $me,
        ]);

        // End whole call only if classic 1:1 and nobody else joined
        $joined = $call->participants()->where('status', 'joined')->count();
        $pending = $call->participants()->whereIn('status', ['invited', 'ringing'])->count();
        if (($call->scope ?? 'dm') === 'dm' && $joined <= 1 && $pending === 0) {
            $call->status = 'rejected';
            $call->ended_at = now();
            $call->save();
            $this->recordChatHistory($call);
        }

        return response()->json(['status' => 'success', 'call' => $this->serializeCall($call->fresh(['participants.user']), $me)]);
    }

    public function end(Request $request): JsonResponse
    {
        $data = $request->validate([
            'call_id' => ['required', 'integer'],
            'end_for_all' => ['nullable', 'boolean'],
        ]);

        $me = (int) Auth::id();
        $call = CallLog::query()->with('participants')->find($data['call_id']);

        if (! $call || ! $call->isParticipant($me)) {
            return response()->json(['status' => 'error', 'message' => 'Call not found'], 404);
        }

        $endForAll = ! empty($data['end_for_all']) && (int) $call->caller_id === $me;
        $part = $call->participants()->where('user_id', $me)->first();

        if ($part) {
            $part->status = 'left';
            $part->left_at = now();
            $part->save();
        }

        $others = $call->participants()
            ->whereIn('status', ['joined', 'invited', 'ringing'])
            ->where('user_id', '!=', $me)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($endForAll || (($call->scope ?? 'dm') === 'dm')) {
            foreach ($others as $peerId) {
                $this->pushSignal($call, $me, $peerId, 'hangup', [
                    'call_uuid' => $call->call_uuid,
                    'status' => 'ended',
                ]);
                $call->participants()->where('user_id', $peerId)->whereIn('status', ['joined', 'invited', 'ringing'])
                    ->update(['status' => 'left', 'left_at' => now()]);
            }
            if (! in_array($call->status, ['ended', 'rejected', 'missed', 'failed'], true)) {
                $call->status = 'ended';
                $call->ended_at = now();
                if ($call->answered_at) {
                    $call->duration_seconds = max(0, $call->answered_at->diffInSeconds($call->ended_at));
                }
                $call->save();
                $this->recordChatHistory($call);
            }
        } else {
            foreach ($others as $peerId) {
                $this->pushSignal($call, $me, $peerId, 'peer-left', [
                    'user_id' => $me,
                    'call_uuid' => $call->call_uuid,
                ]);
            }
            $still = $call->participants()->where('status', 'joined')->count();
            if ($still <= 1) {
                $call->status = 'ended';
                $call->ended_at = now();
                if ($call->answered_at) {
                    $call->duration_seconds = max(0, $call->answered_at->diffInSeconds($call->ended_at));
                }
                $call->save();
                $this->recordChatHistory($call);
                foreach ($call->participants()->where('status', 'joined')->pluck('user_id') as $peerId) {
                    $this->pushSignal($call, $me, (int) $peerId, 'hangup', ['call_uuid' => $call->call_uuid]);
                }
            }
        }

        return response()->json(['status' => 'success', 'call' => $this->serializeCall($call->fresh(['participants.user']), $me)]);
    }

    public function signal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'call_id' => ['required', 'integer'],
            'type' => ['required', 'in:offer,answer,ice,connected,ringing'],
            'target_id' => ['nullable', 'integer'],
            'payload' => ['nullable'],
        ]);

        $me = (int) Auth::id();
        $call = CallLog::query()->find($data['call_id']);

        if (! $call || ! $call->isParticipant($me)) {
            return response()->json(['status' => 'error', 'message' => 'Call not found'], 404);
        }

        if (in_array($call->status, ['ended', 'rejected', 'missed', 'failed'], true)) {
            return response()->json(['status' => 'error', 'message' => 'Call already finished'], 409);
        }

        if ($data['type'] === 'ringing' && $call->status === 'calling') {
            $call->status = 'ringing';
            $call->save();
            $call->participants()->where('user_id', $me)->where('status', 'invited')->update(['status' => 'ringing']);
        }

        if ($data['type'] === 'connected' && in_array($call->status, ['connecting', 'ringing', 'calling'], true)) {
            $call->status = 'connected';
            if (! $call->answered_at) {
                $call->answered_at = now();
            }
            $call->save();
        }

        $targetId = isset($data['target_id']) ? (int) $data['target_id'] : $call->peerId($me);
        if ($targetId <= 0 || $targetId === $me) {
            return response()->json(['status' => 'error', 'message' => 'Invalid target'], 422);
        }

        $payload = $data['payload'] ?? null;
        if (is_array($payload) || is_object($payload)) {
            $payload = json_encode($payload);
        } elseif (is_string($payload) && $payload !== '') {
            // keep as-is
        } else {
            $payload = null;
        }

        // Embed target for multi-peer clients
        $wrapped = [
            'from_id' => $me,
            'target_id' => $targetId,
            'data' => is_string($payload) ? (json_decode($payload, true) ?? $payload) : $payload,
        ];

        $this->pushSignal($call, $me, $targetId, $data['type'], $wrapped);

        return response()->json(['status' => 'success']);
    }

    public function poll(Request $request): JsonResponse
    {
        $me = (int) Auth::id();
        $this->expireStaleCalls();

        $incomingIds = CallParticipant::query()
            ->where('user_id', $me)
            ->whereIn('status', ['invited', 'ringing'])
            ->pluck('call_id')
            ->all();

        $incoming = CallLog::query()
            ->with(['caller:id,fname,lname,image', 'participants.user'])
            ->whereIn('id', $incomingIds ?: [0])
            ->whereIn('status', CallLog::activeStatuses())
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (CallLog $c) => $this->serializeCall($c, $me))
            ->values();

        // Legacy fallback for old 1:1 rows without participants
        if ($incoming->isEmpty()) {
            $incoming = CallLog::query()
                ->with(['caller:id,fname,lname,image'])
                ->where('receiver_id', $me)
                ->whereIn('status', ['calling', 'ringing'])
                ->orderByDesc('id')
                ->limit(5)
                ->get()
                ->map(fn (CallLog $c) => $this->serializeCall($c, $me))
                ->values();
        }

        $signals = CallSignal::query()
            ->where('receiver_id', $me)
            ->whereNull('consumed_at')
            ->orderBy('id')
            ->limit(120)
            ->get();

        $ids = $signals->pluck('id')->all();
        if ($ids !== []) {
            CallSignal::query()->whereIn('id', $ids)->update(['consumed_at' => now()]);
        }

        $payload = $signals->map(function (CallSignal $s) {
            $body = $s->payload;
            $decoded = null;
            if (is_string($body) && $body !== '') {
                $decoded = json_decode($body, true);
            }

            return [
                'id' => $s->id,
                'call_id' => $s->call_id,
                'sender_id' => $s->sender_id,
                'type' => $s->type,
                'payload' => $decoded ?? $body,
            ];
        })->values();

        $active = $this->activeCallRowFor($me);

        return response()->json([
            'incoming' => $incoming,
            'signals' => $payload,
            'active' => $active ? $this->serializeCall($active, $me) : null,
            'live_calls' => CallLog::liveCallSummaries(),
            'on_call_ids' => CallLog::activeOnCallUserIds(),
        ]);
    }

    private function expireStaleCalls(): void
    {
        $timeout = (int) config('webrtc.ring_timeout', 45);
        $cutoff = now()->subSeconds($timeout);

        $staleParts = CallParticipant::query()
            ->whereIn('status', ['invited', 'ringing'])
            ->where('created_at', '<=', $cutoff)
            ->get();

        foreach ($staleParts as $part) {
            $part->status = 'missed';
            $part->left_at = now();
            $part->save();

            $call = CallLog::query()->find($part->call_id);
            if (! $call) {
                continue;
            }
            $this->pushSignal($call, (int) $part->user_id, (int) $call->caller_id, 'missed', [
                'call_uuid' => $call->call_uuid,
                'user_id' => (int) $part->user_id,
            ]);

            $pending = $call->participants()->whereIn('status', ['invited', 'ringing'])->count();
            $joined = $call->participants()->where('status', 'joined')->count();
            if ($pending === 0 && $joined <= 1 && in_array($call->status, ['calling', 'ringing'], true)) {
                $call->status = 'missed';
                $call->ended_at = now();
                $call->save();
                $this->recordChatHistory($call);
            }
        }
    }

    private function userInActiveCall(int $userId, ?int $exceptCallId = null): bool
    {
        $q = CallParticipant::query()
            ->where('user_id', $userId)
            ->whereIn('status', ['invited', 'ringing', 'joined'])
            ->whereHas('call', fn ($c) => $c->whereIn('status', CallLog::activeStatuses()));

        if ($exceptCallId) {
            $q->where('call_id', '!=', $exceptCallId);
        }

        if ($q->exists()) {
            return true;
        }

        // Legacy
        $legacy = CallLog::query()
            ->whereIn('status', CallLog::activeStatuses())
            ->where(function ($inner) use ($userId) {
                $inner->where('caller_id', $userId)->orWhere('receiver_id', $userId);
            });
        if ($exceptCallId) {
            $legacy->where('id', '!=', $exceptCallId);
        }

        return $legacy->exists();
    }

    private function activeCallRowFor(int $userId): ?CallLog
    {
        $callId = CallParticipant::query()
            ->where('user_id', $userId)
            ->where('status', 'joined')
            ->whereHas('call', fn ($c) => $c->whereIn('status', CallLog::activeStatuses()))
            ->orderByDesc('id')
            ->value('call_id');

        return $callId ? CallLog::query()->with(['participants.user', 'caller'])->find($callId) : null;
    }

    private function participantJoined(CallLog $call, int $userId): bool
    {
        return $call->participants()->where('user_id', $userId)->where('status', 'joined')->exists()
            || (int) $call->caller_id === $userId;
    }

    private function isGroupMember(int $groupId, int $userId): bool
    {
        if ($groupId <= 0 || $userId <= 0) {
            return false;
        }

        return ChatGroup::query()->whereKey($groupId)
            ->whereHas('members', fn ($q) => $q->where('hrm_employee.id', $userId))
            ->exists();
    }

    private function pushSignal(CallLog $call, int $senderId, int $receiverId, string $type, mixed $payload = null): void
    {
        if ($receiverId <= 0) {
            return;
        }
        if (is_array($payload) || is_object($payload)) {
            $payload = json_encode($payload);
        }

        CallSignal::query()->create([
            'call_id' => $call->id,
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'type' => $type,
            'payload' => $payload,
        ]);
    }

    private function recordChatHistory(CallLog $call): void
    {
        try {
            $exists = ChatMessage::query()
                ->where('message_type', 'call')
                ->where('message', 'like', '%"call_id":'.$call->id.'%')
                ->exists();
            if ($exists) {
                return;
            }

            $label = $call->call_type === 'video' ? 'Video call' : 'Audio call';
            if (($call->scope ?? 'dm') === 'group') {
                $label = ($call->call_type === 'video' ? 'Group video call' : 'Group audio call');
            }
            $status = match ($call->status) {
                'rejected' => 'Declined',
                'missed' => 'Missed',
                'failed' => 'Failed',
                'ended' => $call->duration_seconds > 0
                    ? $this->formatDuration((int) $call->duration_seconds)
                    : 'Ended',
                default => ucfirst($call->status),
            };

            $payload = json_encode([
                'call_id' => $call->id,
                'call_type' => $call->call_type,
                'scope' => $call->scope ?? 'dm',
                'status' => $call->status,
                'duration' => (int) $call->duration_seconds,
                'label' => $label,
                'detail' => $status,
            ]);

            ChatMessage::query()->create([
                'sender_id' => $call->caller_id,
                'receiver_id' => $call->group_id ? null : $call->receiver_id,
                'group_id' => $call->group_id,
                'message' => $payload,
                'message_type' => 'call',
                'file_path' => null,
                'reply_to' => null,
                'status' => 'sent',
                'seen' => 0,
                'is_deleted' => 0,
                'is_edited' => 0,
                'timestamp' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function formatDuration(int $seconds): string
    {
        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }

    private function serializeCall(CallLog $call, int $me): array
    {
        $participants = $call->relationLoaded('participants')
            ? $call->participants
            : $call->participants()->with('user')->get();

        $list = $participants->map(function (CallParticipant $p) {
            return [
                'user_id' => (int) $p->user_id,
                'role' => $p->role,
                'status' => $p->status,
                'user' => $this->employeeBrief($p->user),
            ];
        })->values()->all();

        $peer = null;
        if (($call->scope ?? 'dm') === 'dm') {
            $peerId = $call->peerId($me);
            $peer = $this->employeeBrief(Employee::query()->find($peerId));
        }

        return [
            'id' => $call->id,
            'call_uuid' => $call->call_uuid,
            'scope' => $call->scope ?? 'dm',
            'group_id' => $call->group_id ? (int) $call->group_id : null,
            'caller_id' => (int) $call->caller_id,
            'receiver_id' => $call->receiver_id ? (int) $call->receiver_id : null,
            'call_type' => $call->call_type,
            'status' => $call->status,
            'started_at' => $call->started_at?->toIso8601String(),
            'answered_at' => $call->answered_at?->toIso8601String(),
            'ended_at' => $call->ended_at?->toIso8601String(),
            'duration_seconds' => (int) $call->duration_seconds,
            'is_caller' => (int) $call->caller_id === $me,
            'is_host' => (int) $call->caller_id === $me,
            'peer' => $peer,
            'caller' => $this->employeeBrief($call->relationLoaded('caller') ? $call->caller : $call->caller()->first()),
            'participants' => $list,
        ];
    }

    private function employeeBrief($employee): ?array
    {
        if (! $employee) {
            return null;
        }

        return [
            'id' => (int) $employee->id,
            'name' => trim(($employee->fname ?? '').' '.($employee->lname ?? '')),
            'fname' => $employee->fname,
            'lname' => $employee->lname,
            'image' => $employee->profile_image_url ?? null,
            'job_title' => $employee->job_title ?? null,
        ];
    }

    private function iceServers(): array
    {
        $servers = [];

        foreach ((array) config('webrtc.stun_servers', []) as $url) {
            if ($url) {
                $servers[] = ['urls' => $url];
            }
        }

        $turnUrls = (array) config('webrtc.turn_servers', []);
        // Legacy single key support
        if ($turnUrls === [] && config('webrtc.turn_server')) {
            $turnUrls = [(string) config('webrtc.turn_server')];
        }

        if ($turnUrls !== []) {
            $entry = ['urls' => count($turnUrls) === 1 ? $turnUrls[0] : array_values($turnUrls)];
            if (config('webrtc.turn_username')) {
                $entry['username'] = (string) config('webrtc.turn_username');
                $entry['credential'] = (string) config('webrtc.turn_password');
            }
            $servers[] = $entry;
        } elseif (config('webrtc.use_public_turn_fallback', true)) {
            // Free OpenRelay — works across NAT when you have no own TURN.
            // For production traffic, replace with your Metered/Twilio/coturn credentials.
            $servers[] = [
                'urls' => [
                    'turn:openrelay.metered.ca:80',
                    'turn:openrelay.metered.ca:80?transport=tcp',
                    'turn:openrelay.metered.ca:443',
                    'turns:openrelay.metered.ca:443?transport=tcp',
                ],
                'username' => 'openrelayproject',
                'credential' => 'openrelayproject',
            ];
        }

        if ($servers === []) {
            $servers[] = ['urls' => 'stun:stun.l.google.com:19302'];
        }

        return $servers;
    }
}
