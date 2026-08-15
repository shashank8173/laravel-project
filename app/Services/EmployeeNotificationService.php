<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeNotification;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EmployeeNotificationService
{
    /**
     * Push a short in-app notification to one employee.
     */
    public function notify(
        int|Employee $recipient,
        string $type,
        string $title,
        ?string $body = null,
        ?string $link = null,
        int|Employee|null $actor = null
    ): ?EmployeeNotification {
        $employeeId = $recipient instanceof Employee ? (int) $recipient->id : (int) $recipient;
        if ($employeeId <= 0) {
            return null;
        }

        $actorId = null;
        if ($actor instanceof Employee) {
            $actorId = (int) $actor->id;
        } elseif (is_int($actor) && $actor > 0) {
            $actorId = $actor;
        }

        // Don't notify yourself for your own action
        if ($actorId && $actorId === $employeeId) {
            return null;
        }

        return EmployeeNotification::query()->create([
            'employee_id' => $employeeId,
            'actor_id' => $actorId,
            'type' => $type,
            'title' => mb_substr(trim($title), 0, 255),
            'body' => $body !== null ? mb_substr(trim(strip_tags($body)), 0, 500) : null,
            'link' => $link,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  iterable<int|Employee>  $recipients
     */
    public function notifyMany(
        iterable $recipients,
        string $type,
        string $title,
        ?string $body = null,
        ?string $link = null,
        int|Employee|null $actor = null
    ): void {
        $seen = [];
        foreach ($recipients as $recipient) {
            $id = $recipient instanceof Employee ? (int) $recipient->id : (int) $recipient;
            if ($id <= 0 || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $this->notify($id, $type, $title, $body, $link, $actor);
        }
    }

    /**
     * Copy legacy hrm_notification rows (announcements) into the in-app inbox
     * when this employee is in send_to and no matching inbox row exists yet.
     */
    public function syncAnnouncementsForEmployee(int $employeeId): int
    {
        if ($employeeId <= 0) {
            return 0;
        }

        try {
            if (! Schema::hasTable('hrm_notification') || ! Schema::hasTable('hrm_employee_notifications')) {
                return 0;
            }
        } catch (\Throwable) {
            return 0;
        }

        $id = (string) $employeeId;
        $announcements = Notification::query()
            ->where(function ($q) use ($id) {
                $q->where('send_to', $id)
                    ->orWhere('send_to', 'like', $id.',%')
                    ->orWhere('send_to', 'like', '%,'.$id.',%')
                    ->orWhere('send_to', 'like', '%,'.$id);
            })
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $created = 0;
        foreach ($announcements as $ann) {
            $exists = EmployeeNotification::query()
                ->where('employee_id', $employeeId)
                ->where('type', 'announcement')
                ->where('title', $ann->title)
                ->exists();

            if ($exists) {
                continue;
            }

            $plain = Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $ann->description)) ?: ''), 500);

            EmployeeNotification::query()->create([
                'employee_id' => $employeeId,
                'actor_id' => $ann->sent_by ?: null,
                'type' => 'announcement',
                'title' => mb_substr(trim((string) $ann->title), 0, 255),
                'body' => $plain !== '' ? $plain : null,
                'link' => url('/my-notifications'),
                'created_at' => $this->parseAnnouncementTime($ann),
            ]);
            $created++;
        }

        return $created;
    }

    public function unreadCount(int $employeeId): int
    {
        return EmployeeNotification::query()
            ->where('employee_id', $employeeId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * @return Collection<int, EmployeeNotification>
     */
    public function latest(int $employeeId, int $limit = 12): Collection
    {
        return EmployeeNotification::query()
            ->with('actor:id,fname,lname')
            ->where('employee_id', $employeeId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Notifications newer than a given id (for polling + sound).
     *
     * @return Collection<int, EmployeeNotification>
     */
    public function since(int $employeeId, int $afterId, int $limit = 20): Collection
    {
        return EmployeeNotification::query()
            ->with('actor:id,fname,lname')
            ->where('employee_id', $employeeId)
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function markAllRead(int $employeeId): int
    {
        return EmployeeNotification::query()
            ->where('employee_id', $employeeId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function markRead(int $employeeId, int $notificationId): bool
    {
        $row = EmployeeNotification::query()
            ->where('employee_id', $employeeId)
            ->where('id', $notificationId)
            ->first();

        if (! $row) {
            return false;
        }

        $row->markRead();

        return true;
    }

    private function parseAnnouncementTime(Notification $ann): Carbon
    {
        $date = trim((string) ($ann->date ?? ''));
        $time = trim((string) ($ann->time ?? ''));
        if ($date === '') {
            return now();
        }

        foreach (['d-m-Y h:i:s', 'd-m-Y H:i:s', 'd-m-Y h:i:s A', 'Y-m-d H:i:s'] as $fmt) {
            try {
                $parsed = Carbon::createFromFormat($fmt, trim($date.' '.$time), config('app.timezone'));
                if ($parsed) {
                    return $parsed;
                }
            } catch (\Throwable) {
                // try next
            }
        }

        try {
            return Carbon::parse(trim($date.' '.$time), config('app.timezone'));
        } catch (\Throwable) {
            return now();
        }
    }
}
