<?php

namespace App\Http\Controllers;

use App\Models\CallLog;
use App\Models\ChatGroup;
use App\Models\ChatMessage;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class ChatController extends Controller
{
    private const ONLINE_TTL = 90;

    public function index(): View
    {
        $me = (int) Auth::id();
        $this->markOnline($me);

        $employees = Employee::query()
            ->where('id', '!=', $me)
            ->orderBy('fname')
            ->orderBy('lname')
            ->get(['id', 'fname', 'lname', 'image', 'job_title']);

        return view('chat.index', [
            'employees' => $employees,
            'meName' => trim((Auth::user()->fname ?? '').' '.(Auth::user()->lname ?? '')),
            'meImage' => Auth::user()->profile_image_url ?? asset('assets/img/profiles/avatar-02.jpg'),
        ]);
    }

    public function heartbeat(): JsonResponse
    {
        $this->markOnline((int) Auth::id());

        return response()->json(['ok' => true]);
    }

    public function contacts(): JsonResponse
    {
        $me = (int) Auth::id();
        $this->markOnline($me);

        $employees = Employee::query()
            ->where('id', '!=', $me)
            ->orderBy('fname')
            ->orderBy('lname')
            ->get(['id', 'fname', 'lname', 'image', 'job_title']);

        $dmMessages = ChatMessage::query()
            ->where('is_deleted', 0)
            ->whereNull('group_id')
            ->where(function ($q) use ($me) {
                $q->where('sender_id', $me)->orWhere('receiver_id', $me);
            })
            ->orderByDesc('message_id')
            ->get(['message_id', 'sender_id', 'receiver_id', 'message', 'message_type', 'timestamp', 'seen']);

        $lastByPeer = [];
        foreach ($dmMessages as $m) {
            $peer = (int) $m->sender_id === $me ? (int) $m->receiver_id : (int) $m->sender_id;
            if ($peer && ! isset($lastByPeer[$peer])) {
                $lastByPeer[$peer] = $m;
            }
        }

        $unreadBySender = ChatMessage::query()
            ->where('receiver_id', $me)
            ->where('seen', 0)
            ->where('is_deleted', 0)
            ->whereNull('group_id')
            ->selectRaw('sender_id, COUNT(*) as c')
            ->groupBy('sender_id')
            ->pluck('c', 'sender_id');

        $onlineMap = $this->onlineMap($employees->pluck('id')->all());
        $onCallIds = CallLog::activeOnCallUserIds();
        $onCallSet = array_fill_keys($onCallIds, true);
        $liveCalls = CallLog::liveCallSummaries();

        $employeeRows = $employees->map(function (Employee $e) use ($lastByPeer, $unreadBySender, $onlineMap, $onCallSet) {
            $last = $lastByPeer[$e->id] ?? null;
            $online = ! empty($onlineMap[$e->id]);
            $onCall = isset($onCallSet[$e->id]);

            return [
                'type' => 'user',
                'id' => $e->id,
                'fname' => $e->fname,
                'lname' => $e->lname,
                'name' => trim(($e->fname ?? '').' '.($e->lname ?? '')),
                'job_title' => $e->job_title,
                'image' => $e->profile_image_url,
                'is_creator' => false,
                'last_message' => $last ? $this->previewText($last) : null,
                'last_at' => $last?->timestamp?->toIso8601String(),
                'last_ts' => $last?->timestamp?->getTimestamp() ?? 0,
                'unread' => (int) ($unreadBySender[$e->id] ?? 0),
                'online' => $online,
                'on_call' => $onCall,
            ];
        })->sort(function (array $a, array $b) {
            if (($a['on_call'] ?? false) !== ($b['on_call'] ?? false)) {
                return ($a['on_call'] ?? false) ? -1 : 1;
            }
            if ($a['online'] !== $b['online']) {
                return $a['online'] ? -1 : 1;
            }
            if (($a['last_ts'] ?? 0) !== ($b['last_ts'] ?? 0)) {
                return ($b['last_ts'] ?? 0) <=> ($a['last_ts'] ?? 0);
            }

            return strcasecmp((string) $a['name'], (string) $b['name']);
        })->values();

        $groups = ChatGroup::query()
            ->whereHas('members', fn ($q) => $q->where('hrm_employee.id', $me))
            ->orderBy('group_name')
            ->get(['group_id', 'group_name', 'created_by']);

        $groupIds = $groups->pluck('group_id')->all();
        $groupLast = [];
        $groupUnread = [];

        if ($groupIds) {
            $groupMsgs = ChatMessage::query()
                ->where('is_deleted', 0)
                ->whereIn('group_id', $groupIds)
                ->orderByDesc('message_id')
                ->get(['message_id', 'group_id', 'message', 'message_type', 'timestamp']);

            foreach ($groupMsgs as $m) {
                $gid = (int) $m->group_id;
                if (! isset($groupLast[$gid])) {
                    $groupLast[$gid] = $m;
                }
            }

            $groupUnread = ChatMessage::query()
                ->whereIn('group_id', $groupIds)
                ->where('sender_id', '!=', $me)
                ->where('seen', 0)
                ->where('is_deleted', 0)
                ->selectRaw('group_id, COUNT(*) as c')
                ->groupBy('group_id')
                ->pluck('c', 'group_id')
                ->all();
        }

        $groupRows = $groups->map(function (ChatGroup $g) use ($me, $groupLast, $groupUnread) {
            $last = $groupLast[$g->group_id] ?? null;

            return [
                'type' => 'group',
                'id' => $g->group_id,
                'name' => $g->group_name,
                'is_creator' => (int) $g->created_by === $me,
                'last_message' => $last ? $this->previewText($last) : null,
                'last_at' => $last?->timestamp?->toIso8601String(),
                'last_ts' => $last?->timestamp?->getTimestamp() ?? 0,
                'unread' => (int) ($groupUnread[$g->group_id] ?? 0),
                'online' => false,
            ];
        })->sortByDesc(fn ($row) => $row['last_ts'] ?? 0)->values();

        $callHistory = collect(CallLog::historyPeersFor($me))->map(function (array $row) use ($onlineMap, $onCallSet) {
            if (($row['type'] ?? '') === 'user') {
                $id = (int) ($row['id'] ?? 0);
                $row['online'] = ! empty($onlineMap[$id]);
                $row['on_call'] = isset($onCallSet[$id]);
            }

            return $row;
        })->values();

        return response()->json([
            'employees' => $employeeRows,
            'groups' => $groupRows,
            'call_history' => $callHistory,
            'live_calls' => $liveCalls,
            'on_call_ids' => $onCallIds,
        ]);
    }

    public function messages(Request $request): JsonResponse
    {
        $me = (int) Auth::id();
        $type = $request->get('type', 'user');
        $id = (int) $request->get('id');

        if ($type === 'group' && ! $this->isGroupMember($id, $me)) {
            return response()->json(['status' => 'error', 'message' => 'Not a member of this group'], 403);
        }

        $query = ChatMessage::query()
            ->with([
                'sender:id,fname,lname,image',
                'replyTo.sender:id,fname,lname',
            ]);

        if ($type === 'group') {
            $query->where('group_id', $id);
        } else {
            $query->where(function ($q) use ($me, $id) {
                $q->where(function ($inner) use ($me, $id) {
                    $inner->where('sender_id', $me)->where('receiver_id', $id);
                })->orWhere(function ($inner) use ($me, $id) {
                    $inner->where('sender_id', $id)->where('receiver_id', $me);
                });
            });
        }

        $messages = $query->orderByDesc('message_id')->limit(100)->get()->sortBy('message_id')->values();

        if ($type === 'user') {
            ChatMessage::query()
                ->where('sender_id', $id)
                ->where('receiver_id', $me)
                ->where('seen', 0)
                ->update(['seen' => 1, 'status' => 'read']);
        } else {
            ChatMessage::query()
                ->where('group_id', $id)
                ->where('sender_id', '!=', $me)
                ->where('seen', 0)
                ->update(['seen' => 1, 'status' => 'read']);
        }

        $payload = $messages->map(fn (ChatMessage $m) => $this->serializeMessage($m, $me));

        return response()->json([
            'messages' => $payload,
            'unread_count' => $this->totalUnread($me),
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:user,group'],
            'id' => ['required', 'integer'],
            'message' => ['nullable', 'string', 'max:5000'],
            'reply_to' => ['nullable', 'integer'],
            'file' => ['nullable', 'file', 'max:51200'],
        ]);

        $messageText = trim((string) ($data['message'] ?? ''));
        $hasFile = $request->hasFile('file');

        if ($messageText === '' && ! $hasFile) {
            return response()->json(['status' => 'error', 'message' => 'Message or file required'], 422);
        }

        if ($hasFile) {
            $upload = $request->file('file');
            $mime = strtolower((string) ($upload->getMimeType() ?: ''));
            $ext = strtolower((string) $upload->getClientOriginalExtension());
            $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp3', 'wav', 'ogg', 'webm', 'm4a', 'aac', 'mp4', 'mov', 'avi', '3gp', 'mkv', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar'];
            $allowedMimePrefix = ['image/', 'audio/', 'video/'];
            $allowedMimeExact = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip',
                'application/x-zip-compressed',
                'application/x-rar-compressed',
                'application/octet-stream',
                'text/plain',
            ];
            $mimeOk = $mime === ''
                || collect($allowedMimePrefix)->contains(fn ($p) => str_starts_with($mime, $p))
                || in_array($mime, $allowedMimeExact, true);
            $extOk = $ext === '' || in_array($ext, $allowedExt, true);
            if (! $mimeOk || ! $extOk) {
                return response()->json(['status' => 'error', 'message' => 'Unsupported file type'], 422);
            }
        }

        if ($data['type'] === 'group' && ! $this->isGroupMember((int) $data['id'], (int) Auth::id())) {
            return response()->json(['status' => 'error', 'message' => 'Not a member of this group'], 403);
        }

        $lineCount = $messageText === '' ? 0 : substr_count($messageText, "\n") + 1;
        if ($lineCount > 100) {
            return response()->json(['status' => 'error', 'message' => 'Maximum 100 lines allowed per message'], 422);
        }

        $messageType = 'text';
        $filePath = null;

        if ($hasFile) {
            $file = $request->file('file');
            $mime = (string) $file->getMimeType();
            $messageType = match (true) {
                str_starts_with($mime, 'image/') => 'image',
                str_starts_with($mime, 'audio/') => 'audio',
                str_starts_with($mime, 'video/') => 'video',
                // MediaRecorder often reports video/webm or audio/webm
                str_contains($mime, 'webm') && str_contains($mime, 'audio') => 'audio',
                str_contains($mime, 'webm') => 'video',
                default => 'file',
            };

            $dir = public_path('chat_uploads');
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $ext = strtolower((string) $file->getClientOriginalExtension());
            if ($ext === '' || $ext === 'bin') {
                $ext = match (true) {
                    str_contains($mime, 'jpeg') => 'jpg',
                    str_contains($mime, 'png') => 'png',
                    str_contains($mime, 'webp') => 'webp',
                    str_contains($mime, 'gif') => 'gif',
                    str_contains($mime, 'webm') => 'webm',
                    str_contains($mime, 'ogg') => 'ogg',
                    str_contains($mime, 'mp4') => 'mp4',
                    str_contains($mime, 'mpeg') || str_contains($mime, 'mp3') => 'mp3',
                    str_contains($mime, 'wav') => 'wav',
                    str_contains($mime, 'aac') || str_contains($mime, 'm4a') => 'm4a',
                    default => 'bin',
                };
            }

            $name = uniqid('chat_', true).'.'.$ext;
            $file->move($dir, $name);
            $filePath = 'chat_uploads/'.$name;

            if ($messageText === '') {
                $messageText = $file->getClientOriginalName() ?: ($messageType === 'audio' ? 'Voice note' : ($messageType === 'video' ? 'Video' : 'Attachment'));
            }
        }

        $payload = [
            'sender_id' => Auth::id(),
            'message' => $messageText,
            'message_type' => $messageType,
            'file_path' => $filePath,
            'reply_to' => ! empty($data['reply_to']) ? (int) $data['reply_to'] : null,
            'timestamp' => now(),
            'status' => 'sent',
            'seen' => 0,
            'is_deleted' => 0,
            'is_edited' => 0,
        ];

        if ($data['type'] === 'group') {
            $payload['group_id'] = $data['id'];
            $payload['receiver_id'] = null;
        } else {
            $payload['receiver_id'] = $data['id'];
            $payload['group_id'] = null;
        }

        $msg = ChatMessage::create($payload);
        $msg->load(['sender:id,fname,lname,image', 'replyTo.sender:id,fname,lname']);

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message_id' => $msg->message_id,
            'file_path' => $msg->file_path,
            'message_type' => $msg->message_type,
            'message' => $this->serializeMessage($msg, (int) Auth::id()),
        ]);
    }

    public function unreadCount(): JsonResponse
    {
        $me = (int) Auth::id();
        $count = $this->totalUnread($me);

        $latest = null;
        if ($count > 0) {
            $groupIds = ChatGroup::query()
                ->whereHas('members', fn ($q) => $q->where('hrm_employee.id', $me))
                ->pluck('group_id');

            $msg = ChatMessage::query()
                ->with(['sender:id,fname,lname,image', 'group:group_id,group_name'])
                ->where('seen', 0)
                ->where('is_deleted', 0)
                ->where('sender_id', '!=', $me)
                ->where(function ($q) use ($me, $groupIds) {
                    $q->where('receiver_id', $me)
                        ->orWhereIn('group_id', $groupIds);
                })
                ->orderByDesc('message_id')
                ->first();

            if ($msg) {
                $from = trim(($msg->sender?->fname ?? '').' '.($msg->sender?->lname ?? '')) ?: 'Someone';
                $preview = $this->previewText($msg);
                $chatType = $msg->group_id ? 'group' : 'user';
                $chatId = $msg->group_id ?: $msg->sender_id;
                $chatName = $msg->group_id ? ($msg->group?->group_name ?: 'Group') : $from;

                $latest = [
                    'message_id' => $msg->message_id,
                    'from' => $from,
                    'preview' => $preview,
                    'chat_type' => $chatType,
                    'chat_id' => (int) $chatId,
                    'chat_name' => $chatName,
                    'image' => $msg->sender?->profile_image_url,
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'count' => $count,
            'unread_count' => $count,
            'latest' => $latest,
        ]);
    }

    public function groupMembers(Request $request): JsonResponse
    {
        $groupId = (int) $request->get('group_id');
        $me = (int) Auth::id();

        if ($groupId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'Invalid group'], 422);
        }

        if (! $this->isGroupMember($groupId, $me)) {
            return response()->json(['status' => 'error', 'message' => 'Not a member of this group'], 403);
        }

        $group = ChatGroup::query()->find($groupId);
        if (! $group) {
            return response()->json(['status' => 'error', 'message' => 'Group not found'], 404);
        }

        $members = $group->members()
            ->orderBy('hrm_employee.fname')
            ->orderBy('hrm_employee.lname')
            ->get(['hrm_employee.id', 'hrm_employee.fname', 'hrm_employee.lname', 'hrm_employee.image'])
            ->map(fn (Employee $e) => [
                'id' => (int) $e->id,
                'fname' => $e->fname,
                'lname' => $e->lname,
                'name' => trim(($e->fname ?? '').' '.($e->lname ?? '')),
                'image' => $e->profile_image_url,
            ])
            ->values();

        return response()->json($members);
    }

    public function createGroup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'group_name' => ['required', 'string', 'max:255'],
            'members' => ['nullable', 'array'],
            'members.*' => ['integer'],
        ]);

        $me = (int) Auth::id();

        try {
            $groupId = DB::transaction(function () use ($data, $me) {
                $group = ChatGroup::create([
                    'group_name' => trim($data['group_name']),
                    'created_by' => $me,
                ]);

                $memberIds = collect($data['members'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0 && $id !== $me)
                    ->unique()
                    ->values();

                $valid = Employee::query()->whereIn('id', $memberIds)->pluck('id')->all();
                $attach = array_values(array_unique(array_merge([$me], $valid)));
                $group->members()->syncWithoutDetaching($attach);

                return $group->group_id;
            });
        } catch (Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }

        return response()->json(['status' => 'success', 'group_id' => $groupId]);
    }

    public function addGroupMembers(Request $request): JsonResponse
    {
        $data = $request->validate([
            'group_id' => ['required', 'integer'],
            'members' => ['required', 'array', 'min:1'],
            'members.*' => ['integer'],
        ]);

        $me = (int) Auth::id();
        $group = ChatGroup::query()->findOrFail($data['group_id']);

        if ((int) $group->created_by !== $me) {
            return response()->json(['status' => 'error', 'message' => 'Only the group creator can add members'], 403);
        }

        $ids = collect($data['members'])->map(fn ($id) => (int) $id)->filter()->unique()->values();
        $valid = Employee::query()->whereIn('id', $ids)->pluck('id')->all();
        $group->members()->syncWithoutDetaching($valid);

        return response()->json(['status' => 'success']);
    }

    public function removeGroupMember(Request $request): JsonResponse
    {
        $data = $request->validate([
            'group_id' => ['required', 'integer'],
            'user_id' => ['required', 'integer'],
        ]);

        $me = (int) Auth::id();
        $group = ChatGroup::query()->findOrFail($data['group_id']);

        if ((int) $group->created_by !== $me) {
            return response()->json(['status' => 'error', 'message' => 'Only the group creator can remove members'], 403);
        }

        if ((int) $data['user_id'] === $me) {
            return response()->json(['status' => 'error', 'message' => 'You cannot remove yourself from the group'], 422);
        }

        $detached = $group->members()->detach((int) $data['user_id']);
        if (! $detached) {
            return response()->json(['status' => 'error', 'message' => 'User is not a member of this group'], 404);
        }

        return response()->json(['status' => 'success']);
    }

    public function clearChat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:user,group'],
            'id' => ['required', 'integer'],
        ]);

        $me = (int) Auth::id();
        // Clear only the current user's own messages — never delete others' messages
        $query = ChatMessage::query()
            ->where('is_deleted', 0)
            ->where('sender_id', $me);

        if ($data['type'] === 'user') {
            $peer = (int) $data['id'];
            $query->where('receiver_id', $peer);
        } else {
            if (! $this->isGroupMember((int) $data['id'], $me)) {
                return response()->json(['status' => 'error', 'message' => 'Not a member of this group'], 403);
            }
            $query->where('group_id', (int) $data['id']);
        }

        $updated = $query->update(['is_deleted' => 1]);
        if ($updated === 0) {
            return response()->json(['status' => 'error', 'message' => 'No messages of yours to clear'], 422);
        }

        return response()->json(['status' => 'success']);
    }

    public function exitGroup(Request $request): JsonResponse
    {
        $data = $request->validate(['group_id' => ['required', 'integer']]);
        $me = (int) Auth::id();
        $group = ChatGroup::query()->findOrFail($data['group_id']);

        $detached = $group->members()->detach($me);
        if (! $detached) {
            return response()->json(['status' => 'error', 'message' => 'You are not a member of this group'], 404);
        }

        return response()->json(['status' => 'success']);
    }

    public function deleteGroup(Request $request): JsonResponse
    {
        $data = $request->validate(['group_id' => ['required', 'integer']]);
        $me = (int) Auth::id();
        $group = ChatGroup::query()->findOrFail($data['group_id']);

        if ((int) $group->created_by !== $me) {
            return response()->json(['status' => 'error', 'message' => 'Only the group creator can delete the group'], 403);
        }

        DB::transaction(function () use ($group) {
            $group->members()->detach();
            $group->delete();
        });

        return response()->json(['status' => 'success']);
    }

    public function editGroup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'group_id' => ['required', 'integer'],
            'new_name' => ['required', 'string', 'max:255'],
        ]);

        $me = (int) Auth::id();
        $group = ChatGroup::query()->findOrFail($data['group_id']);

        $isMember = $group->members()->where('hrm_employee.id', $me)->exists();
        if (! $isMember) {
            return response()->json(['status' => 'error', 'message' => 'You must be a group member to edit the name'], 403);
        }

        $group->group_name = trim($data['new_name']);
        $group->save();

        return response()->json(['status' => 'success']);
    }

    public function deleteMessage(Request $request): JsonResponse
    {
        $data = $request->validate(['message_id' => ['required', 'integer']]);
        $me = (int) Auth::id();

        $msg = ChatMessage::query()
            ->where('message_id', $data['message_id'])
            ->where('is_deleted', 0)
            ->first();

        if (! $msg) {
            return response()->json(['status' => 'error', 'message' => 'Message not found'], 404);
        }

        if ((int) $msg->sender_id !== $me) {
            return response()->json(['status' => 'error', 'message' => 'You can only delete your own messages'], 403);
        }

        $msg->is_deleted = 1;
        $msg->save();

        return response()->json(['status' => 'success']);
    }

    public function editMessage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message_id' => ['required', 'integer'],
            'new_message' => ['required', 'string', 'max:5000'],
        ]);

        $me = (int) Auth::id();
        $msg = ChatMessage::query()->where('message_id', $data['message_id'])->first();

        if (! $msg || (int) $msg->sender_id !== $me) {
            return response()->json(['status' => 'error', 'message' => 'Not allowed'], 403);
        }

        if ((int) $msg->is_deleted === 1) {
            return response()->json(['status' => 'error', 'message' => 'Cannot edit deleted message'], 422);
        }

        $msg->message = trim($data['new_message']);
        $msg->is_edited = 1;
        $msg->save();

        return response()->json(['status' => 'success']);
    }

    private function serializeMessage(ChatMessage $m, int $me): array
    {
        $sender = $m->sender;
        $reply = $m->replyTo;

        return [
            'message_id' => $m->message_id,
            'sender_id' => $m->sender_id,
            'receiver_id' => $m->receiver_id,
            'group_id' => $m->group_id,
            'message' => $m->message,
            'message_type' => $m->message_type,
            'file_path' => $m->file_path,
            'reply_to' => $m->reply_to,
            'timestamp' => $m->timestamp?->toIso8601String(),
            'status' => $m->status,
            'seen' => (int) $m->seen,
            'is_deleted' => (int) $m->is_deleted,
            'is_edited' => (int) $m->is_edited,
            'is_me' => (int) $m->sender_id === $me,
            'fname' => $sender?->fname,
            'lname' => $sender?->lname,
            'image' => $sender?->profile_image_url,
            'reply_message' => $reply?->message,
            'reply_sender_id' => $reply?->sender_id,
            'reply_fname' => $reply?->sender?->fname,
            'reply_lname' => $reply?->sender?->lname,
            'sender' => $sender ? [
                'id' => $sender->id,
                'fname' => $sender->fname,
                'lname' => $sender->lname,
                'image' => $sender->profile_image_url,
            ] : null,
        ];
    }

    private function totalUnread(int $me): int
    {
        $dm = ChatMessage::query()
            ->where('receiver_id', $me)
            ->where('sender_id', '!=', $me)
            ->where('seen', 0)
            ->where('is_deleted', 0)
            ->count();

        $groupIds = ChatGroup::query()
            ->whereHas('members', fn ($q) => $q->where('hrm_employee.id', $me))
            ->pluck('group_id');

        $groupUnread = ChatMessage::query()
            ->whereIn('group_id', $groupIds)
            ->where('sender_id', '!=', $me)
            ->where('seen', 0)
            ->where('is_deleted', 0)
            ->count();

        return $dm + $groupUnread;
    }

    private function previewText(ChatMessage $m): string
    {
        $type = (string) ($m->message_type ?: 'text');
        if ($type === 'image') {
            return '📷 Photo';
        }
        if ($type === 'audio') {
            return '🎵 Audio';
        }
        if ($type === 'video') {
            return '🎬 Video';
        }
        if ($type === 'file' || $m->file_path) {
            return '📎 '.($m->message ?: 'File');
        }
        if ($type === 'call') {
            $info = json_decode((string) $m->message, true) ?: [];
            $label = $info['label'] ?? (($info['call_type'] ?? '') === 'video' ? 'Video call' : 'Audio call');
            $detail = $info['detail'] ?? '';

            return $detail !== '' ? "📞 {$label} · {$detail}" : "📞 {$label}";
        }

        $text = trim((string) $m->message);

        return mb_strlen($text) > 60 ? mb_substr($text, 0, 57).'…' : $text;
    }

    private function isGroupMember(int $groupId, int $userId): bool
    {
        if ($groupId <= 0 || $userId <= 0) {
            return false;
        }

        return ChatGroup::query()
            ->where('group_id', $groupId)
            ->whereHas('members', fn ($q) => $q->where('hrm_employee.id', $userId))
            ->exists();
    }

    private function markOnline(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }
        Cache::put($this->presenceKey($userId), time(), self::ONLINE_TTL);
    }

    /**
     * @param  array<int, int|string>  $employeeIds
     * @return array<int, bool>
     */
    private function onlineMap(array $employeeIds): array
    {
        $map = [];
        foreach ($employeeIds as $id) {
            $id = (int) $id;
            if ($id > 0 && Cache::has($this->presenceKey($id))) {
                $map[$id] = true;
            }
        }

        return $map;
    }

    private function presenceKey(int $userId): string
    {
        return 'chat_presence_'.$userId;
    }
}
