<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CallLog extends Model
{
    protected $table = 'hrm_call_logs';

    protected $fillable = [
        'call_uuid',
        'scope',
        'caller_id',
        'receiver_id',
        'group_id',
        'call_type',
        'status',
        'started_at',
        'answered_at',
        'ended_at',
        'duration_seconds',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'answered_at' => 'datetime',
            'ended_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function caller(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'caller_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'receiver_id');
    }

    public function signals(): HasMany
    {
        return $this->hasMany(CallSignal::class, 'call_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(CallParticipant::class, 'call_id');
    }

    public static function activeStatuses(): array
    {
        return ['calling', 'ringing', 'connecting', 'connected'];
    }

    public function isGroupCall(): bool
    {
        return ($this->scope ?? 'dm') === 'group' || ! empty($this->group_id);
    }

    public function isParticipant(int $userId): bool
    {
        if ($this->participants()->where('user_id', $userId)->whereNotIn('status', ['left', 'rejected', 'missed'])->exists()) {
            return true;
        }

        return (int) $this->caller_id === $userId || (int) $this->receiver_id === $userId;
    }

    public function peerId(int $userId): int
    {
        // Legacy 1:1 helper
        return (int) $this->caller_id === $userId
            ? (int) ($this->receiver_id ?: 0)
            : (int) $this->caller_id;
    }

    public function joinedUserIds(): array
    {
        return $this->participants()
            ->where('status', 'joined')
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** User IDs currently joined on any active call */
    public static function activeOnCallUserIds(): array
    {
        return CallParticipant::query()
            ->where('status', 'joined')
            ->whereHas('call', fn ($q) => $q->whereIn('status', self::activeStatuses()))
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /** Live call summaries visible to all employees */
    public static function liveCallSummaries(): array
    {
        return self::query()
            ->with(['participants.user:id,fname,lname,image', 'caller:id,fname,lname,image'])
            ->whereIn('status', self::activeStatuses())
            ->whereHas('participants', fn ($q) => $q->where('status', 'joined'))
            ->orderByDesc('id')
            ->limit(15)
            ->get()
            ->map(function (self $call) {
                $joined = $call->participants->where('status', 'joined')->values();
                $names = $joined->map(function (CallParticipant $p) {
                    $u = $p->user;
                    return $u ? trim(($u->fname ?? '').' '.($u->lname ?? '')) : ('#'.$p->user_id);
                })->filter()->values()->all();

                return [
                    'id' => $call->id,
                    'call_type' => $call->call_type,
                    'scope' => $call->scope ?? 'dm',
                    'status' => $call->status,
                    'participant_ids' => $joined->pluck('user_id')->map(fn ($id) => (int) $id)->values()->all(),
                    'participant_names' => $names,
                    'label' => ($call->call_type === 'video' ? 'Video' : 'Audio')
                        .' call · '.max(1, count($names)).' on call',
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Unique people/groups the user has called with (latest call first).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function historyPeersFor(int $me): array
    {
        $calls = self::query()
            ->with([
                'caller:id,fname,lname,image',
                'receiver:id,fname,lname,image',
                'participants.user:id,fname,lname,image',
            ])
            ->whereIn('status', ['ended', 'missed', 'rejected', 'failed'])
            ->where(function ($q) use ($me) {
                $q->where('caller_id', $me)
                    ->orWhere('receiver_id', $me)
                    ->orWhereHas('participants', fn ($p) => $p->where('user_id', $me));
            })
            ->orderByDesc('ended_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $rows = [];
        foreach ($calls as $call) {
            $when = $call->ended_at ?: $call->started_at;
            $detail = match ($call->status) {
                'rejected' => 'Declined',
                'missed' => 'Missed',
                'failed' => 'Failed',
                'ended' => ((int) $call->duration_seconds > 0)
                    ? sprintf('%d:%02d', intdiv((int) $call->duration_seconds, 60), ((int) $call->duration_seconds) % 60)
                    : 'Ended',
                default => ucfirst((string) $call->status),
            };
            $kind = $call->call_type === 'video' ? 'Video call' : 'Audio call';
            $outgoing = (int) $call->caller_id === $me;

            if ($call->isGroupCall() && $call->group_id) {
                $key = 'g:'.(int) $call->group_id;
                if (isset($rows[$key])) {
                    continue;
                }
                $group = ChatGroup::query()->find($call->group_id);
                if (! $group) {
                    continue;
                }
                $rows[$key] = [
                    'type' => 'group',
                    'id' => (int) $call->group_id,
                    'name' => $group->group_name,
                    'image' => null,
                    'job_title' => null,
                    'is_creator' => (int) $group->created_by === $me,
                    'call_type' => $call->call_type,
                    'call_status' => $call->status,
                    'direction' => $outgoing ? 'outgoing' : 'incoming',
                    'preview' => ($outgoing ? '↗ ' : '↙ ').$kind.' · '.$detail,
                    'last_at' => $when?->toIso8601String(),
                    'last_ts' => $when?->getTimestamp() ?? 0,
                    'online' => false,
                    'on_call' => false,
                    'unread' => 0,
                ];
                continue;
            }

            $peerIds = [];
            foreach ($call->participants as $p) {
                $uid = (int) $p->user_id;
                if ($uid > 0 && $uid !== $me) {
                    $peerIds[$uid] = $p->user;
                }
            }
            if ($peerIds === []) {
                $other = (int) $call->caller_id === $me
                    ? (int) ($call->receiver_id ?: 0)
                    : (int) $call->caller_id;
                if ($other > 0) {
                    $peerIds[$other] = (int) $call->caller_id === $other ? $call->caller : $call->receiver;
                }
            }

            foreach ($peerIds as $uid => $user) {
                $key = 'u:'.$uid;
                if (isset($rows[$key])) {
                    continue;
                }
                if (! $user) {
                    $user = Employee::query()->find($uid);
                }
                if (! $user) {
                    continue;
                }
                $name = trim(($user->fname ?? '').' '.($user->lname ?? ''));
                $rows[$key] = [
                    'type' => 'user',
                    'id' => $uid,
                    'name' => $name !== '' ? $name : ('User #'.$uid),
                    'fname' => $user->fname,
                    'lname' => $user->lname,
                    'image' => $user->profile_image_url ?? null,
                    'job_title' => $user->job_title ?? null,
                    'is_creator' => false,
                    'call_type' => $call->call_type,
                    'call_status' => $call->status,
                    'direction' => $outgoing ? 'outgoing' : 'incoming',
                    'preview' => ($outgoing ? '↗ ' : '↙ ').$kind.' · '.$detail,
                    'last_at' => $when?->toIso8601String(),
                    'last_ts' => $when?->getTimestamp() ?? 0,
                    'online' => false,
                    'on_call' => false,
                    'unread' => 0,
                ];
            }
        }

        return array_values($rows);
    }
}
