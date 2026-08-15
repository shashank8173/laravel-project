<?php

namespace App\View\Composers;

use App\Models\ChatGroup;
use App\Models\ChatMessage;
use App\Models\EmployeeNotification;
use App\Models\LeaveApplied;
use App\Models\Resignation;
use App\Models\Ticket;
use App\Services\EmployeeNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class LayoutComposer
{
    public function compose(View $view): void
    {
        $user = Auth::user();
        if (! $user) {
            $view->with([
                'layoutBadges' => [],
                'layoutNotifications' => collect(),
                'layoutNotifyLatestId' => 0,
            ]);

            return;
        }

        $me = (int) $user->id;

        $chatUnread = ChatMessage::query()
            ->where('seen', 0)
            ->where('is_deleted', 0)
            ->where(function ($q) use ($me) {
                $q->where(function ($inner) use ($me) {
                    $inner->where('receiver_id', $me)->where('sender_id', '!=', $me);
                })->orWhere(function ($inner) use ($me) {
                    $groupIds = ChatGroup::query()
                        ->whereHas('members', fn ($m) => $m->where('hrm_employee.id', $me))
                        ->pluck('group_id');
                    $inner->whereIn('group_id', $groupIds)->where('sender_id', '!=', $me);
                });
            })
            ->count();

        $hasInbox = false;
        try {
            $hasInbox = Schema::hasTable('hrm_employee_notifications');
        } catch (\Throwable) {
            $hasInbox = false;
        }

        $unread = 0;
        $layoutNotifications = collect();
        $latestId = 0;

        if ($hasInbox) {
            $service = app(EmployeeNotificationService::class);
            $service->syncAnnouncementsForEmployee($me);
            $unread = $service->unreadCount($me);
            $layoutNotifications = $service->latest($me, 10);
            $latestId = (int) (EmployeeNotification::query()->where('employee_id', $me)->max('id') ?: 0);
        }

        $view->with([
            'layoutBadges' => [
                'pending_leaves' => LeaveApplied::query()->whereIn('status', [0, 1])->count(),
                'open_tickets' => Ticket::query()->whereIn('Status', ['Open', 'In Progress'])->count(),
                'pending_resignations' => Resignation::query()->whereRaw('LOWER(status) = ?', ['pending'])->count(),
                'notifications' => $unread,
                'chat_unread' => $chatUnread,
            ],
            'layoutNotifications' => $layoutNotifications,
            'layoutNotifyLatestId' => $latestId,
        ]);
    }
}
