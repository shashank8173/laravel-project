<?php

namespace App\Http\Controllers;

use App\Models\AdminLoginLog;
use App\Models\ArchivedEmployee;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\LeaveApplied;
use App\Models\Resignation;
use App\Models\Ticket;
use App\Models\UserAttendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(): View
    {
        $today = Carbon::today();
        $year = (int) now()->year;

        $activeEmployees = Employee::query()->where('status', 1)->where('archive_status', 0)->count();
        $inactiveEmployees = Employee::query()->where('status', 0)->where('archive_status', 0)->count();
        $archivedEmployees = Schema::hasTable('archived_employees')
            ? ArchivedEmployee::query()->count()
            : Employee::query()->where('archive_status', 1)->count();

        $attendanceToday = UserAttendance::query()->whereDate('clock_in_time', $today)->count();
        $stillInToday = UserAttendance::query()
            ->whereDate('clock_in_time', $today)
            ->whereNull('clock_out_time')
            ->count();

        $pendingLeaves = LeaveApplied::query()->whereIn('status', [0, 1])->count();
        $approvedLeaves = LeaveApplied::query()->where('status', 2)->count();
        $rejectedLeaves = LeaveApplied::query()->where('status', 3)->count();

        $openTickets = Ticket::query()
            ->whereNotIn('Status', ['Closed', 'Resolved', 'closed', 'resolved'])
            ->count();
        $closedTickets = Ticket::query()
            ->whereIn('Status', ['Closed', 'Resolved', 'closed', 'resolved'])
            ->count();

        $pendingExpenses = Expense::query()->whereRaw('LOWER(status) = ?', ['pending'])->count();
        $approvedExpenses = Expense::query()->whereRaw('LOWER(status) = ?', ['approved'])->count();
        $rejectedExpenses = Expense::query()->whereRaw('LOWER(status) = ?', ['rejected'])->count();
        $expenseAmountMonth = (float) Expense::query()
            ->whereRaw("DATE_FORMAT(expense_date, '%Y-%m') = ?", [now()->format('Y-m')])
            ->sum('amount');

        $pendingResignations = Resignation::query()->whereRaw('LOWER(status) = ?', ['pending'])->count();
        $acceptedResignations = Resignation::query()->whereRaw('LOWER(status) IN (?, ?)', ['accepted', 'approved'])->count();

        $adminLoginsToday = AdminLoginLog::query()
            ->whereDate('timestamp', $today)
            ->where('action', 'login')
            ->count();
        $adminLogoutsToday = AdminLoginLog::query()
            ->whereDate('timestamp', $today)
            ->where('action', 'logout')
            ->count();

        $employeeLoginsToday = Schema::hasTable('hrm_login_detail')
            ? (int) DB::table('hrm_login_detail')->whereDate('date_time', $today)->count()
            : 0;

        $stats = [
            'active_employees' => $activeEmployees,
            'inactive_employees' => $inactiveEmployees,
            'archived_employees' => $archivedEmployees,
            'attendance_today' => $attendanceToday,
            'still_in_today' => $stillInToday,
            'pending_leaves' => $pendingLeaves,
            'open_tickets' => $openTickets,
            'pending_expenses' => $pendingExpenses,
            'pending_resignations' => $pendingResignations,
            'admin_logins_today' => $adminLoginsToday,
            'employee_logins_today' => $employeeLoginsToday,
            'expense_amount_month' => $expenseAmountMonth,
        ];

        $charts = [
            'employeesPie' => [
                'labels' => ['Active', 'Inactive', 'Archived'],
                'data' => [$activeEmployees, $inactiveEmployees, $archivedEmployees],
            ],
            'loginPie' => [
                'labels' => ['Admin logins', 'Admin logouts', 'Employee logins'],
                'data' => [$adminLoginsToday, $adminLogoutsToday, $employeeLoginsToday],
            ],
            'leavesPie' => [
                'labels' => ['Pending / In review', 'Approved', 'Rejected'],
                'data' => [$pendingLeaves, $approvedLeaves, $rejectedLeaves],
            ],
            'ticketsPie' => [
                'labels' => ['Open', 'Closed / Resolved'],
                'data' => [$openTickets, $closedTickets],
            ],
            'expensesPie' => [
                'labels' => ['Pending', 'Approved', 'Rejected'],
                'data' => [$pendingExpenses, $approvedExpenses, $rejectedExpenses],
            ],
            'resignationsPie' => [
                'labels' => ['Pending', 'Accepted / Approved', 'Other'],
                'data' => [
                    $pendingResignations,
                    $acceptedResignations,
                    max(0, Resignation::query()->count() - $pendingResignations - $acceptedResignations),
                ],
            ],
            'departmentsBar' => $this->departmentBreakdown(),
            'attendanceLine' => $this->attendanceLastDays(14),
            'leavesBar' => $this->leavesByMonth($year),
            'adminLoginBar' => $this->adminLoginLastDays(7),
            'employeeLoginBar' => $this->employeeLoginLastDays(7),
        ];

        return view('analytics.index', [
            'stats' => $stats,
            'charts' => $charts,
            'year' => $year,
        ]);
    }

    /**
     * @return array{labels:array<int,string>,data:array<int,int>}
     */
    private function departmentBreakdown(): array
    {
        $rows = DB::table('hrm_employee as e')
            ->leftJoin('hrm_department as d', 'd.id', '=', 'e.department_id')
            ->where('e.archive_status', 0)
            ->where('e.status', 1)
            ->select(DB::raw("COALESCE(d.name, 'Unassigned') as department"), DB::raw('COUNT(*) as total'))
            ->groupBy('d.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'labels' => $rows->pluck('department')->all(),
            'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    /**
     * @return array{labels:array<int,string>,data:array<int,int>}
     */
    private function attendanceLastDays(int $days): array
    {
        $labels = [];
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $labels[] = $day->format('d M');
            $data[] = UserAttendance::query()->whereDate('clock_in_time', $day)->count();
        }

        return compact('labels', 'data');
    }

    /**
     * @return array{labels:array<int,string>,data:array<int,int>}
     */
    private function leavesByMonth(int $year): array
    {
        $raw = LeaveApplied::query()
            ->select(DB::raw('MONTH(created_at) as m'), DB::raw('COUNT(*) as total'))
            ->whereYear('created_at', $year)
            ->groupBy('m')
            ->pluck('total', 'm');

        $labels = [];
        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $labels[] = Carbon::create()->month($m)->format('M');
            $data[] = (int) ($raw[$m] ?? 0);
        }

        return compact('labels', 'data');
    }

    /**
     * @return array{labels:array<int,string>,login:array<int,int>,logout:array<int,int>}
     */
    private function adminLoginLastDays(int $days): array
    {
        $labels = [];
        $login = [];
        $logout = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $labels[] = $day->format('d M');
            $login[] = AdminLoginLog::query()->whereDate('timestamp', $day)->where('action', 'login')->count();
            $logout[] = AdminLoginLog::query()->whereDate('timestamp', $day)->where('action', 'logout')->count();
        }

        return compact('labels', 'login', 'logout');
    }

    /**
     * @return array{labels:array<int,string>,data:array<int,int>}
     */
    private function employeeLoginLastDays(int $days): array
    {
        $labels = [];
        $data = [];
        $hasTable = Schema::hasTable('hrm_login_detail');

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $labels[] = $day->format('d M');
            $data[] = $hasTable
                ? (int) DB::table('hrm_login_detail')->whereDate('date_time', $day)->count()
                : 0;
        }

        return compact('labels', 'data');
    }
}
