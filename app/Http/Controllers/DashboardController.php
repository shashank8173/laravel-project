<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\Holiday;
use App\Models\LeaveApplied;
use App\Models\Resignation;
use App\Models\Ticket;
use App\Models\UserAttendance;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function admin(): View
    {
        $user = Auth::user();
        abort_unless($user && $user->canAccessAdminDashboard(), 403);

        $stats = [
            'employees' => Employee::query()->where('status', 1)->where('archive_status', 0)->count(),
            'pending_leaves' => LeaveApplied::query()->whereIn('status', [0, 1])->count(),
            'holidays' => Holiday::query()->where('year', now()->year)->count(),
            'open_tickets' => Ticket::query()->whereIn('Status', ['Open', 'In Progress'])->count(),
            'pending_resignations' => Resignation::query()->whereRaw('LOWER(status) = ?', ['pending'])->count(),
            'pending_expenses' => Expense::query()->whereRaw('LOWER(status) = ?', ['pending'])->count(),
            'attendance_today' => UserAttendance::query()->whereDate('clock_in_time', today())->count(),
        ];

        $recentLeaves = LeaveApplied::query()
            ->with(['employee', 'leaveType'])
            ->latest('created_at')
            ->limit(8)
            ->get();

        $recentTickets = Ticket::query()
            ->with(['category', 'employee'])
            ->latest('CreatedAt')
            ->limit(6)
            ->get();

        $setupItems = [];
        $setupDone = 0;
        $setupTotal = 0;
        $setupPercent = 100;
        if ($user->isSuperAdmin()) {
            $setupItems = OptionalSetupController::checklistStatus();
            $setupDone = collect($setupItems)->where('ready', true)->count();
            $setupTotal = count($setupItems);
            $setupPercent = $setupTotal > 0 ? (int) round(($setupDone / $setupTotal) * 100) : 100;
        }

        return view('dashboard.admin', compact(
            'stats',
            'recentLeaves',
            'recentTickets',
            'setupItems',
            'setupDone',
            'setupTotal',
            'setupPercent'
        ));
    }

    public function employee(): View
    {
        $user = Auth::user();

        $myLeaves = LeaveApplied::query()
            ->with('leaveType')
            ->where('emp_id', $user->id)
            ->latest('created_at')
            ->limit(8)
            ->get();

        $myTickets = Ticket::query()
            ->where('EmployeeID', $user->id)
            ->latest('CreatedAt')
            ->limit(5)
            ->get();

        $todayAttendance = UserAttendance::query()
            ->where('user_id', $user->id)
            ->whereDate('clock_in_time', today())
            ->latest('id')
            ->first();

        $stats = [
            'my_leaves' => LeaveApplied::query()->where('emp_id', $user->id)->count(),
            'pending_leaves' => LeaveApplied::query()->where('emp_id', $user->id)->whereIn('status', [0, 1])->count(),
            'open_tickets' => Ticket::query()->where('EmployeeID', $user->id)->whereIn('Status', ['Open', 'In Progress'])->count(),
            'punched_in' => (bool) ($todayAttendance && ! $todayAttendance->clock_out_time),
        ];

        $upcomingHolidays = Holiday::query()
            ->where('year', now()->year)
            ->get()
            ->map(function (Holiday $h) {
                $raw = trim((string) $h->date);
                if ($raw === '') {
                    return null;
                }
                try {
                    $date = \Carbon\Carbon::parse($raw)->startOfDay();
                } catch (\Throwable) {
                    return null;
                }
                if ($date->lt(today())) {
                    return null;
                }

                return [
                    'name' => $h->name,
                    'display_date' => $date->format('d M'),
                    'sort' => $date->timestamp,
                ];
            })
            ->filter()
            ->sortBy('sort')
            ->take(5)
            ->values();

        return view('dashboard.employee', compact('user', 'myLeaves', 'myTickets', 'todayAttendance', 'stats', 'upcomingHolidays'));
    }
}
