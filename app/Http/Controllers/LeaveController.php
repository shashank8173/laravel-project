<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveApplied;
use App\Models\LeaveType;
use App\Models\ReportingManager;
use App\Services\EmployeeNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LeaveController extends Controller
{
    public function adminIndex(Request $request): View
    {
        $viewer = Auth::user();
        abort_unless($viewer && $viewer->canManageTeam(), 403);

        $status = $request->get('status');
        $period = $request->get('period'); // today | tomorrow | yesterday | range
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $employeeId = $request->get('employee_id');
        $q = trim((string) $request->get('q', ''));

        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $teamIds = $this->scopedEmployeeIds($viewer);

        $counts = [
            'today' => $this->countOnLeaveForDate($today, $teamIds),
            'tomorrow' => $this->countOnLeaveForDate($tomorrow, $teamIds),
            'yesterday' => $this->countOnLeaveForDate($yesterday, $teamIds),
            'pending' => $this->scopedLeavesQuery($teamIds)->whereIn('status', [0, 1])->count(),
            'approved' => $this->scopedLeavesQuery($teamIds)->where('status', 2)->count(),
            'rejected' => $this->scopedLeavesQuery($teamIds)->where('status', 3)->count(),
        ];

        $excluded = config('hrm.excluded_employee_ids', [14]);

        $employees = Employee::query()
            ->with(['department:id,name', 'designation:id,name'])
            ->where('archive_status', 0)
            ->when($excluded !== [], fn ($q) => $q->whereNotIn('id', $excluded))
            ->when($teamIds !== null, fn ($q) => $q->whereIn('id', $teamIds ?: [0]))
            ->orderBy('fname')
            ->orderBy('lname')
            ->get(['id', 'fname', 'lname', 'department_id', 'designation_id', 'emp_id']);

        $leaves = $this->scopedLeavesQuery($teamIds)
            ->with(['employee', 'leaveType', 'approver'])
            ->when(
                in_array($period, ['today', 'tomorrow', 'yesterday'], true),
                function ($query) use ($period, $today, $tomorrow, $yesterday) {
                    $date = match ($period) {
                        'today' => $today,
                        'tomorrow' => $tomorrow,
                        default => $yesterday,
                    };
                    $this->scopeOnLeaveForDate($query, $date);
                },
                function ($query) use ($status, $period, $dateFrom, $dateTo) {
                    $query->when($status !== null && $status !== '', fn ($inner) => $inner->where('status', (int) $status));

                    if (($period === 'range' || ($dateFrom && $dateTo)) && $dateFrom && $dateTo) {
                        $query->whereDate('start_date', '<=', $dateTo)
                            ->whereDate('end_date', '>=', $dateFrom);
                    }
                }
            )
            ->when($employeeId, fn ($query) => $query->where('emp_id', (int) $employeeId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('leave_reason', 'like', "%{$q}%")
                        ->orWhereHas('employee', function ($emp) use ($q) {
                            $emp->where('fname', 'like', "%{$q}%")
                                ->orWhere('lname', 'like', "%{$q}%")
                                ->orWhereRaw("CONCAT(fname, ' ', lname) LIKE ?", ["%{$q}%"])
                                ->orWhere('emp_id', 'like', "%{$q}%");
                        })
                        ->orWhereHas('leaveType', function ($type) use ($q) {
                            $type->where('name', 'like', "%{$q}%");
                        });
                });
            })
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('leaves.admin', compact(
            'leaves',
            'status',
            'period',
            'dateFrom',
            'dateTo',
            'counts',
            'today',
            'tomorrow',
            'yesterday',
            'employees',
            'employeeId',
            'q'
        ));
    }

    private function countOnLeaveForDate(string $date, ?array $teamIds = null): int
    {
        return (int) $this->scopedLeavesQuery($teamIds)
            ->where('status', 2)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->selectRaw('COUNT(DISTINCT emp_id) as aggregate')
            ->value('aggregate');
    }

    private function scopeOnLeaveForDate($query, string $date)
    {
        return $query
            ->where('status', 2)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date);
    }

    public function employeeIndex(): View
    {
        $empId = Auth::id();

        $leaves = LeaveApplied::query()
            ->with(['leaveType', 'approver'])
            ->where('emp_id', $empId)
            ->latest('created_at')
            ->paginate(20);

        $leaveTypes = LeaveType::query()->orderBy('name')->get();
        $balances = [];
        foreach ($leaveTypes as $type) {
            $balances[$type->id] = $this->remainingBalance((int) $empId, (int) $type->id);
        }

        $base = LeaveApplied::query()->where('emp_id', $empId);
        $stats = [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->whereIn('status', [0, 1])->count(),
            'approved' => (clone $base)->where('status', 2)->count(),
            'declined' => (clone $base)->where('status', 3)->count(),
        ];

        return view('leaves.employee', compact('leaves', 'leaveTypes', 'stats', 'balances'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'leave_type_id' => ['required', 'integer', 'exists:hrm_leave_type,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'leave_reason' => ['required', 'string', 'max:2000'],
            'day_type' => ['required', 'integer'],
            'no_of_days' => ['required', 'integer', 'min:1'],
        ]);

        $empId = (int) Auth::id();
        $leaveTypeId = (int) $data['leave_type_id'];
        $days = (int) $data['no_of_days'];
        $remaining = $this->remainingBalance($empId, $leaveTypeId);

        if ($days > $remaining) {
            return back()
                ->withErrors(['no_of_days' => "Insufficient leave balance. Remaining: {$remaining} day(s)."])
                ->withInput();
        }

        LeaveApplied::create([
            ...$data,
            'emp_id' => $empId,
            'approved_by' => 0,
            'status' => 0,
        ]);

        $employee = Auth::user();
        $managerIds = ReportingManager::query()
            ->where('employee_id', $empId)
            ->pluck('reporting_manager_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        if ($managerIds !== []) {
            app(EmployeeNotificationService::class)->notifyMany(
                $managerIds,
                'leave_applied',
                ($employee?->full_name ?: 'Employee').' applied for leave',
                'From '.$data['start_date'].' to '.$data['end_date'],
                route('leaves.admin'),
                $employee
            );
        }

        return back()->with('success', 'Leave application submitted.');
    }

    public function updateStatus(Request $request, LeaveApplied $leave): RedirectResponse
    {
        $viewer = Auth::user();
        abort_unless($viewer && $viewer->canManageTeam(), 403);
        abort_unless($this->canActOnLeave($viewer, $leave), 403);

        $data = $request->validate([
            'status' => ['required', 'integer', 'in:1,2,3'],
        ]);

        $newStatus = (int) $data['status'];

        if ($newStatus === 2) {
            $remaining = $this->remainingBalance(
                (int) $leave->emp_id,
                (int) $leave->leave_type_id,
                (int) $leave->id
            );

            if ((int) $leave->no_of_days > $remaining) {
                return back()->withErrors([
                    'status' => "Cannot approve: employee has only {$remaining} day(s) remaining for this leave type.",
                ]);
            }
        }

        $leave->update([
            'status' => $newStatus,
            'approved_by' => Auth::id(),
        ]);

        $statusLabel = match ($newStatus) {
            1 => 'marked Pending',
            2 => 'Approved',
            3 => 'Rejected',
            default => 'Updated',
        };
        $title = $newStatus === 2
            ? 'Leave approved'
            : ($newStatus === 3 ? 'Leave rejected' : 'Leave status updated');

        app(EmployeeNotificationService::class)->notify(
            (int) $leave->emp_id,
            'leave_'.$newStatus,
            $title.' · by '.($viewer->full_name ?: 'Manager'),
            'Your leave was '.$statusLabel.'.',
            route('leaves.employee'),
            $viewer
        );

        return back()->with('success', 'Leave status updated.');
    }

    /**
     * Remaining days for a leave type (quota minus new/pending/approved).
     * Rejected leaves do not consume balance.
     */
    private function remainingBalance(int $empId, int $leaveTypeId, ?int $exceptLeaveId = null): int
    {
        $type = LeaveType::query()->find($leaveTypeId);
        if (! $type) {
            return 0;
        }

        $quota = (int) $type->number_of_leave;
        $used = (int) LeaveApplied::query()
            ->where('emp_id', $empId)
            ->where('leave_type_id', $leaveTypeId)
            ->whereIn('status', [0, 1, 2])
            ->when($exceptLeaveId, fn ($q) => $q->where('id', '!=', $exceptLeaveId))
            ->sum('no_of_days');

        return max(0, $quota - $used);
    }

    /**
     * null = no restriction (admin). Empty array = manager with no team.
     *
     * @return list<int>|null
     */
    private function scopedEmployeeIds($viewer): ?array
    {
        if ($viewer->isAdmin()) {
            return null;
        }

        return $viewer->teamEmployeeIds();
    }

    private function scopedLeavesQuery(?array $teamIds)
    {
        $query = LeaveApplied::query();

        if ($teamIds !== null) {
            $query->whereIn('emp_id', $teamIds !== [] ? $teamIds : [0]);
        }

        return $query;
    }

    private function canActOnLeave($viewer, LeaveApplied $leave): bool
    {
        if ($viewer->isAdmin()) {
            return true;
        }

        return ReportingManager::query()
            ->where('reporting_manager_id', $viewer->id)
            ->where('employee_id', $leave->emp_id)
            ->exists();
    }
}
