<?php

namespace App\Http\Controllers;

use App\Models\EmployeeNotification;
use App\Services\EmployeeNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeNotificationController extends Controller
{
    public function __construct(private EmployeeNotificationService $notifications) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $this->notifications->syncAnnouncementsForEmployee((int) $user->id);

        $items = EmployeeNotification::query()
            ->with('actor:id,fname,lname')
            ->where('employee_id', $user->id)
            ->orderByDesc('id')
            ->paginate(30);

        return view('notifications.inbox', compact('items'));
    }

    public function poll(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->notifications->syncAnnouncementsForEmployee((int) $user->id);
        $afterId = (int) $request->query('after_id', 0);

        $fresh = $this->notifications->since((int) $user->id, $afterId);
        $latest = $this->notifications->latest((int) $user->id, 12);
        $unread = $this->notifications->unreadCount((int) $user->id);

        $map = fn (EmployeeNotification $n) => [
            'id' => $n->id,
            'type' => $n->type,
            'title' => $n->title,
            'body' => $n->body,
            'link' => $n->link,
            'actor' => $n->actor?->full_name,
            'unread' => $n->isUnread(),
            'time' => optional($n->created_at)->diffForHumans(),
            'created_at' => optional($n->created_at)->toIso8601String(),
        ];

        return response()->json([
            'unread' => $unread,
            'latest_id' => (int) (EmployeeNotification::query()
                ->where('employee_id', $user->id)
                ->max('id') ?: 0),
            'items' => $latest->map($map)->values(),
            'fresh' => $fresh->map($map)->values(),
            'sound' => (bool) ($user->notify_app_sound ?? true),
        ]);
    }

    public function markRead(Request $request, EmployeeNotification $notification): JsonResponse
    {
        $user = $request->user();
        abort_unless((int) $notification->employee_id === (int) $user->id, 403);

        $notification->markRead();

        return response()->json([
            'ok' => true,
            'unread' => $this->notifications->unreadCount((int) $user->id),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->notifications->markAllRead((int) $user->id);

        return response()->json([
            'ok' => true,
            'unread' => 0,
        ]);
    }
}
