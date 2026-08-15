<?php

namespace App\Http\Controllers;

use App\Models\AttendanceMachineDetail;
use App\Models\Employee;
use App\Models\OfficeTiming;
use App\Models\UserAttendance;
use App\Services\AttendanceGeofence;
use App\Services\AttendanceUploadService;
use App\Services\EmployeeAttendanceReportService;
use App\Services\OfficeTimingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function myAttendance(Request $request): View
    {
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);
        $userId = Auth::id();

        $records = UserAttendance::query()
            ->where('user_id', $userId)
            ->whereMonth('clock_in_time', $month)
            ->whereYear('clock_in_time', $year)
            ->orderByDesc('clock_in_time')
            ->paginate(40)
            ->withQueryString();

        $monthRows = UserAttendance::query()
            ->where('user_id', $userId)
            ->whereMonth('clock_in_time', $month)
            ->whereYear('clock_in_time', $year)
            ->get(['id', 'late_status', 'clock_out_time']);

        $stats = [
            'days' => $monthRows->count(),
            'late' => $monthRows->filter(fn ($r) => strtolower((string) $r->late_status) === 'late')->count(),
            'ontime' => $monthRows->filter(fn ($r) => strtolower((string) ($r->late_status ?: '')) === 'on time')->count(),
            'open' => $monthRows->filter(fn ($r) => empty($r->clock_out_time))->count(),
        ];

        $todayOpen = UserAttendance::query()
            ->where('user_id', $userId)
            ->whereDate('clock_in_time', today())
            ->whereNull('clock_out_time')
            ->latest('id')
            ->first();

        $todayLast = UserAttendance::query()
            ->where('user_id', $userId)
            ->whereDate('clock_in_time', today())
            ->latest('id')
            ->first();

        $machine = null;
        $emp = Auth::user();
        if ($emp->attendance_id) {
            $machine = AttendanceMachineDetail::query()
                ->where('attandance_id', $emp->attendance_id)
                ->where('month', $month)
                ->where('year', (string) $year)
                ->first();
        }

        return view('attendance.employee', compact(
            'records',
            'machine',
            'month',
            'year',
            'stats',
            'todayOpen',
            'todayLast'
        ));
    }

    public function adminReport(Request $request): View
    {
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);
        $employeeId = $request->get('employee_id');

        $employees = Employee::query()
            ->with(['department:id,name', 'designation:id,name'])
            ->where('status', 1)
            ->where('archive_status', 0)
            ->orderBy('fname')
            ->get(['id', 'fname', 'lname', 'attendance_id', 'department_id', 'designation_id', 'emp_id']);

        $query = AttendanceMachineDetail::query()
            ->where('month', $month)
            ->where('year', (string) $year);

        if ($employeeId) {
            $emp = Employee::find($employeeId);
            if ($emp?->attendance_id) {
                $query->where('attandance_id', $emp->attendance_id);
            } else {
                $query->whereRaw('1=0');
            }
        }

        $rows = $query->orderBy('employee_name')->paginate(30)->withQueryString();

        return view('attendance.admin', compact('rows', 'employees', 'month', 'year', 'employeeId'));
    }

    /**
     * Legacy attandance-all-employee.php — day-by-day HRM attendance report.
     */
    public function allEmployees(Request $request, EmployeeAttendanceReportService $report): View
    {
        /** @var Employee $viewer */
        $viewer = Auth::user();
        $isAdmin = $viewer->isAdmin();

        $employeeId = $request->filled('employee_id') ? (int) $request->get('employee_id') : null;
        $month = $request->filled('month') ? (int) $request->get('month') : null;
        $year = $request->filled('year') ? (int) $request->get('year') : null;

        $employees = $report->visibleEmployees($viewer);
        $onLeave = $isAdmin ? $report->employeesOnLeaveToday() : collect();
        $absentToday = $isAdmin ? $report->employeesAbsentToday() : collect();

        $dayRows = [];
        $counts = [
            'present' => 0,
            'saturday' => 0,
            'sunday' => 0,
            'holiday' => 0,
            'leave' => 0,
            'absent' => 0,
            'late' => 0,
        ];
        $selectedEmployee = null;
        $unauthorized = false;
        $filtered = $employeeId && $month && $year;

        if ($filtered) {
            if (! $report->canView($viewer, $employeeId)) {
                $unauthorized = true;
            } else {
                $result = $report->monthReport($employeeId, $month, $year);
                $dayRows = $result['rows'];
                $counts = $result['counts'];
                $selectedEmployee = $result['employee'];
            }
        } else {
            $dayRows = $report->todayRows($viewer);
        }

        return view('attendance.all-employees', [
            'employees' => $employees,
            'employeeId' => $employeeId,
            'month' => $month,
            'year' => $year,
            'isAdmin' => $isAdmin,
            'onLeave' => $onLeave,
            'absentToday' => $absentToday,
            'dayRows' => $dayRows,
            'counts' => $counts,
            'selectedEmployee' => $selectedEmployee,
            'unauthorized' => $unauthorized,
            'filtered' => $filtered,
            'today' => now()->toDateString(),
        ]);
    }

    public function updateRecord(Request $request, OfficeTimingService $timing): RedirectResponse
    {
        /** @var Employee $viewer */
        $viewer = Auth::user();
        abort_unless($viewer->isAdmin(), 403);

        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'employee_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'clock_in' => ['required', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i'],
        ]);

        if ((int) $data['employee_id'] === 14) {
            return back()->withErrors(['attendance' => 'This employee is restricted.']);
        }

        $metrics = $timing->computePunchMetrics(
            $data['date'],
            $data['clock_in'],
            $data['clock_out'] ?? null
        );

        $clockInTime = $data['date'].' '.$data['clock_in'].':00';
        $clockOutTime = ! empty($data['clock_out']) ? $data['date'].' '.$data['clock_out'].':00' : null;

        $payload = array_merge($metrics, [
            'clock_in_time' => $clockInTime,
            'clock_out_time' => $clockOutTime,
            'updated_at' => now(),
        ]);

        $id = (int) ($data['id'] ?? 0);
        if ($id > 0) {
            UserAttendance::query()->where('id', $id)->update($payload);
        } else {
            UserAttendance::query()->create(array_merge($payload, [
                'user_id' => (int) $data['employee_id'],
                'created_at' => now(),
            ]));
        }

        return back()->with('success', 'Attendance updated successfully.');
    }

    public function destroyRecord(Request $request): RedirectResponse
    {
        /** @var Employee $viewer */
        $viewer = Auth::user();
        abort_unless($viewer->isAdmin(), 403);

        $data = $request->validate([
            'delete_id' => ['required', 'integer'],
        ]);

        UserAttendance::query()->where('id', $data['delete_id'])->delete();

        return back()->with('success', 'Attendance record deleted.');
    }

    public function punchIn(Request $request, AttendanceGeofence $geofence): RedirectResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        $check = $geofence->validate((float) $data['latitude'], (float) $data['longitude']);
        if (! $check['ok']) {
            return back()->withErrors(['punch' => $check['message']]);
        }

        $open = UserAttendance::query()
            ->where('user_id', Auth::id())
            ->whereDate('clock_in_time', today())
            ->whereNull('clock_out_time')
            ->first();

        if ($open) {
            return back()->withErrors(['punch' => 'Already punched in. Clock out first.']);
        }

        $timing = OfficeTiming::query()->first();
        $now = now();
        $lateStatus = 'On Time';

        if ($timing?->login_time) {
            $expected = today()->setTimeFromTimeString($timing->login_time);
            if ($timing->relaxation_time) {
                [$h, $m, $s] = array_pad(explode(':', (string) $timing->relaxation_time), 3, '0');
                $expected->addHours((int) $h)->addMinutes((int) $m)->addSeconds((int) $s);
            }
            if ($now->gt($expected)) {
                $lateStatus = 'Late';
            }
        }

        UserAttendance::create([
            'user_id' => Auth::id(),
            'clock_in_time' => $now,
            'clock_in_ip' => $request->ip() ?? '0.0.0.0',
            'clock_in_latitude' => $data['latitude'],
            'clock_in_longitude' => $data['longitude'],
            'clock_in_accuracy' => $data['accuracy'] ?? null,
            'status' => 'Present',
            'late_status' => $lateStatus,
            'status_color' => $lateStatus === 'Late' ? 'red' : 'green',
        ]);

        return back()->with('success', 'Punched in successfully with live location.');
    }

    public function punchOut(Request $request, AttendanceGeofence $geofence): RedirectResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        $check = $geofence->validate((float) $data['latitude'], (float) $data['longitude']);
        if (! $check['ok']) {
            return back()->withErrors(['punch' => $check['message']]);
        }

        $open = UserAttendance::query()
            ->where('user_id', Auth::id())
            ->whereDate('clock_in_time', today())
            ->whereNull('clock_out_time')
            ->latest('id')
            ->first();

        if (! $open) {
            return back()->withErrors(['punch' => 'No open punch-in found for today.']);
        }

        $out = now();
        $mins = $open->clock_in_time->diffInMinutes($out);
        $hours = intdiv($mins, 60);
        $rem = $mins % 60;

        $open->update([
            'clock_out_time' => $out,
            'clock_out_ip' => $request->ip() ?? '0.0.0.0',
            'clock_out_latitude' => $data['latitude'],
            'clock_out_longitude' => $data['longitude'],
            'clock_out_accuracy' => $data['accuracy'] ?? null,
            'total_working_time' => sprintf('%02d:%02d', $hours, $rem),
        ]);

        return back()->with('success', 'Punched out successfully with live location.');
    }

    public function uploadForm(): View
    {
        return view('attendance.upload');
    }

    public function upload(Request $request, AttendanceUploadService $service): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'format' => ['required', 'in:xlsx,csv'],
        ]);

        $file = $data['file'];
        $ext = strtolower($file->getClientOriginalExtension());

        try {
            if ($data['format'] === 'xlsx') {
                if ($ext !== 'xlsx') {
                    return back()->withErrors(['file' => 'Biometric upload requires .xlsx']);
                }
                $result = $service->importBiometricXlsx($file);
            } else {
                if ($ext !== 'csv') {
                    return back()->withErrors(['file' => 'CSV upload requires .csv']);
                }
                $result = $service->importCsv($file);
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        return $result['skipped']
            ? back()->withErrors(['file' => $result['message']])
            : back()->with('success', $result['message']);
    }
}
