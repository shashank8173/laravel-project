<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveApplied;
use App\Models\ReportingManager;
use App\Models\UserAttendance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class EmployeeAttendanceReportService
{
    public function __construct(private readonly OfficeTimingService $officeTiming)
    {
    }

    public function visibleEmployees(Employee $viewer): Collection
    {
        $base = Employee::query()
            ->with(['department:id,name', 'designation:id,name'])
            ->where('archive_status', '!=', 1)
            ->where('id', '!=', 14)
            ->orderBy('fname')
            ->orderBy('lname');

        if ($viewer->isAdmin()) {
            return $base->get(['id', 'fname', 'lname', 'doj', 'email', 'office_email', 'department_id', 'designation_id', 'emp_id']);
        }

        $teamIds = ReportingManager::query()
            ->where('reporting_manager_id', $viewer->id)
            ->pluck('employee_id')
            ->all();

        return $base
            ->where(function ($q) use ($viewer, $teamIds) {
                $q->where('id', $viewer->id);
                if ($teamIds !== []) {
                    $q->orWhereIn('id', $teamIds);
                }
            })
            ->get(['id', 'fname', 'lname', 'doj', 'email', 'office_email', 'department_id', 'designation_id', 'emp_id']);
    }

    public function canView(Employee $viewer, int $employeeId): bool
    {
        if ($employeeId === 14) {
            return false;
        }

        if ($viewer->isAdmin() || (int) $viewer->id === $employeeId) {
            return true;
        }

        return ReportingManager::query()
            ->where('employee_id', $employeeId)
            ->where('reporting_manager_id', $viewer->id)
            ->exists();
    }

    public function employeesOnLeaveToday(): Collection
    {
        $today = now()->toDateString();

        return LeaveApplied::query()
            ->with('employee:id,fname,lname')
            ->where('status', 2)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->get()
            ->filter(fn (LeaveApplied $leave) => $leave->employee)
            ->map(fn (LeaveApplied $leave) => [
                'id' => $leave->emp_id,
                'name' => $leave->employee->full_name,
                'start_date' => optional($leave->start_date)->toDateString(),
                'end_date' => optional($leave->end_date)->toDateString(),
            ])
            ->values();
    }

    public function employeesAbsentToday(): Collection
    {
        $today = now()->toDateString();

        $onLeaveIds = LeaveApplied::query()
            ->where('status', 2)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->pluck('emp_id');

        $presentIds = UserAttendance::query()
            ->whereDate('clock_in_time', $today)
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'absent');
            })
            ->pluck('user_id');

        return Employee::query()
            ->where('status', 1)
            ->where('archive_status', 0)
            ->where('id', '!=', 14)
            ->whereNotIn('id', $onLeaveIds)
            ->whereNotIn('id', $presentIds)
            ->orderBy('fname')
            ->get(['id', 'fname', 'lname'])
            ->map(fn (Employee $e) => [
                'id' => $e->id,
                'name' => $e->full_name,
            ]);
    }

    /**
     * @return array{rows: array<int, array>, counts: array<string, int>, employee: ?Employee}
     */
    public function monthReport(int $employeeId, int $month, int $year): array
    {
        $employee = Employee::query()
            ->where('id', $employeeId)
            ->where('id', '!=', 14)
            ->first(['id', 'fname', 'lname', 'doj', 'email', 'office_email']);

        if (! $employee) {
            return ['rows' => [], 'counts' => $this->emptyCounts(), 'employee' => null];
        }

        $settings = $this->officeTiming->settings();
        [$shortMin, $shortMax] = $this->officeTiming->shortLeaveMinuteRange($settings);

        $holidays = $this->holidayMap($year);
        $approvedLeaves = $this->approvedLeaveDates($employeeId, $month, $year);

        $attendanceRecords = UserAttendance::query()
            ->where('user_id', $employeeId)
            ->whereMonth('clock_in_time', $month)
            ->whereYear('clock_in_time', $year)
            ->when($employee->doj, fn ($q) => $q->where('clock_in_time', '>=', $employee->doj))
            ->orderBy('clock_in_time')
            ->get()
            ->keyBy(fn (UserAttendance $row) => $row->clock_in_time->toDateString());

        $counts = $this->emptyCounts();
        $rows = [];
        $doj = $employee->doj ? Carbon::parse($employee->doj)->startOfDay() : null;
        $today = now()->startOfDay();
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $index = 1;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $dateObj = Carbon::parse($currentDate)->startOfDay();

            if ($doj && $dateObj->lt($doj)) {
                continue;
            }
            if ($dateObj->gt($today)) {
                continue;
            }

            $dayOfWeek = (int) $dateObj->format('N');
            $nonWorking = $this->officeTiming->nonWorkingLabel($dateObj, $settings);
            /** @var UserAttendance|null $record */
            $record = $attendanceRecords->get($currentDate);

            if ($record && strtolower((string) $record->status) !== 'absent') {
                $counts['present']++;
                $lateDisplay = $this->lateDisplay($record, $shortMin, $shortMax);
                if (in_array($record->late_status, ['Late', 'Late (Extra Late)', 'Late (Extra Fine)'], true)) {
                    $counts['late']++;
                }

                $rows[] = [
                    'index' => $index++,
                    'type' => 'present',
                    'employee_name' => $employee->full_name,
                    'date' => $currentDate,
                    'clock_in' => $record->clock_in_time?->format('h:i A'),
                    'clock_out' => $record->clock_out_time?->format('h:i A') ?? 'N/A',
                    'total_working_time' => $record->total_working_time ?? 'N/A',
                    'extra_label' => $record->extra_or_remaining_label
                        ? "{$record->extra_or_remaining_label}: {$record->extra_or_remaining_time}"
                        : 'N/A',
                    'late_status' => $lateDisplay,
                    'status_color' => $record->status_color,
                    'attendance_id' => $record->id,
                    'edit_clock_in' => $record->clock_in_time?->format('H:i'),
                    'edit_clock_out' => $record->clock_out_time?->format('H:i'),
                    'clock_in_latitude' => $record->clock_in_latitude,
                    'clock_in_longitude' => $record->clock_in_longitude,
                    'clock_out_latitude' => $record->clock_out_latitude,
                    'clock_out_longitude' => $record->clock_out_longitude,
                ];
            } elseif (isset($holidays[$currentDate])) {
                $counts['holiday']++;
                $rows[] = $this->specialRow($index++, $employee->full_name, $currentDate, $holidays[$currentDate], 'holiday');
            } elseif (isset($approvedLeaves[$currentDate])) {
                $counts['leave']++;
                $rows[] = $this->specialRow($index++, $employee->full_name, $currentDate, 'Leave', 'leave');
            } elseif ($nonWorking !== '') {
                if ($dayOfWeek === 6) {
                    $counts['saturday']++;
                } elseif ($dayOfWeek === 7) {
                    $counts['sunday']++;
                }
                $rows[] = $this->specialRow($index++, $employee->full_name, $currentDate, $nonWorking, 'off');
            } else {
                $counts['absent']++;
                $absentId = $record?->id;
                $rows[] = $this->specialRow($index++, $employee->full_name, $currentDate, 'Absent', 'absent', $absentId);
            }
        }

        return [
            'rows' => $rows,
            'counts' => $counts,
            'employee' => $employee,
        ];
    }

    public function todayRows(Employee $viewer): array
    {
        $today = now()->toDateString();

        $query = UserAttendance::query()
            ->select('newuser_attendance.*')
            ->join('hrm_employee as he', 'he.id', '=', 'newuser_attendance.user_id')
            ->whereDate('newuser_attendance.clock_in_time', $today)
            ->where('he.id', '!=', 14)
            ->where(function ($q) {
                $q->whereNull('he.doj')
                    ->orWhereColumn('newuser_attendance.clock_in_time', '>=', 'he.doj');
            });

        if ($viewer->isAdmin()) {
            // all employees
        } elseif ($viewer->isReportingManager()) {
            $query->join('hrm_reporting_manager as hrm', 'he.id', '=', 'hrm.employee_id')
                ->where('hrm.reporting_manager_id', $viewer->id);
        } else {
            $query->where('he.id', $viewer->id);
        }

        $records = $query
            ->with('employee:id,fname,lname')
            ->orderBy('newuser_attendance.clock_in_time')
            ->get();

        $onLeaveIds = LeaveApplied::query()
            ->where('status', 2)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->pluck('emp_id')
            ->flip();

        $rows = [];
        $index = 1;
        foreach ($records as $record) {
            $name = $record->employee?->full_name ?? '—';
            $date = $record->clock_in_time?->toDateString() ?? $today;

            if ($onLeaveIds->has($record->user_id)) {
                $rows[] = $this->specialRow($index++, $name, $date, 'Leave', 'leave');
            } elseif (strtolower((string) $record->status) === 'absent') {
                $rows[] = $this->specialRow($index++, $name, $date, 'Absent', 'absent', $record->id);
            } else {
                $rows[] = [
                    'index' => $index++,
                    'type' => 'present',
                    'employee_name' => $name,
                    'date' => $date,
                    'clock_in' => $record->clock_in_time?->format('h:i A'),
                    'clock_out' => $record->clock_out_time?->format('h:i A') ?? 'N/A',
                    'total_working_time' => $record->total_working_time ?? 'N/A',
                    'extra_label' => $record->extra_or_remaining_label
                        ? "{$record->extra_or_remaining_label}: {$record->extra_or_remaining_time}"
                        : 'N/A',
                    'late_status' => $record->late_status,
                    'status_color' => $record->status_color,
                    'attendance_id' => $record->id,
                    'edit_clock_in' => $record->clock_in_time?->format('H:i'),
                    'edit_clock_out' => $record->clock_out_time?->format('H:i'),
                    'employee_id' => $record->user_id,
                    'clock_in_latitude' => $record->clock_in_latitude,
                    'clock_in_longitude' => $record->clock_in_longitude,
                    'clock_out_latitude' => $record->clock_out_latitude,
                    'clock_out_longitude' => $record->clock_out_longitude,
                ];
            }
        }

        return $rows;
    }

    private function holidayMap(int $year): array
    {
        $map = [];
        Holiday::query()->where('year', $year)->get(['name', 'date'])->each(function (Holiday $holiday) use (&$map) {
            $raw = trim((string) $holiday->date);
            if ($raw === '') {
                return;
            }
            try {
                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $raw)) {
                    $converted = Carbon::createFromFormat('d-m-Y', $raw)->toDateString();
                } else {
                    $converted = Carbon::parse($raw)->toDateString();
                }
                $map[$converted] = $holiday->name;
            } catch (\Throwable) {
                // skip invalid holiday dates
            }
        });

        return $map;
    }

    private function approvedLeaveDates(int $employeeId, int $month, int $year): array
    {
        $dates = [];
        LeaveApplied::query()
            ->where('emp_id', $employeeId)
            ->where('status', 2)
            ->where(function ($q) use ($month, $year) {
                $q->where(function ($inner) use ($month, $year) {
                    $inner->whereYear('start_date', $year)->whereMonth('start_date', $month);
                })->orWhere(function ($inner) use ($month, $year) {
                    $inner->whereYear('end_date', $year)->whereMonth('end_date', $month);
                });
            })
            ->get(['start_date', 'end_date'])
            ->each(function (LeaveApplied $leave) use (&$dates, $month, $year) {
                if (! $leave->start_date || ! $leave->end_date) {
                    return;
                }
                foreach (CarbonPeriod::create($leave->start_date->startOfDay(), $leave->end_date->startOfDay()) as $date) {
                    if ((int) $date->month === $month && (int) $date->year === $year) {
                        $dates[$date->toDateString()] = true;
                    }
                }
            });

        return $dates;
    }

    private function lateDisplay(UserAttendance $record, int $shortMin, int $shortMax): string
    {
        $parts = explode(':', (string) $record->extra_or_remaining_time);
        $hours = isset($parts[0]) ? (int) $parts[0] : 0;
        $mins = isset($parts[1]) ? (int) $parts[1] : 0;
        $minutes = ($hours * 60) + $mins;

        if (
            $minutes >= $shortMin
            && $minutes <= $shortMax
            && $record->extra_or_remaining_label === 'Remaining Time'
        ) {
            return 'Short Leave';
        }

        return (string) ($record->late_status ?? '');
    }

    private function specialRow(
        int $index,
        string $name,
        string $date,
        string $label,
        string $type,
        ?int $attendanceId = null
    ): array {
        return [
            'index' => $index,
            'type' => $type,
            'employee_name' => $name,
            'date' => $date,
            'label' => $label,
            'attendance_id' => $attendanceId,
            'edit_clock_in' => '',
            'edit_clock_out' => '',
        ];
    }

    private function emptyCounts(): array
    {
        return [
            'present' => 0,
            'saturday' => 0,
            'sunday' => 0,
            'holiday' => 0,
            'leave' => 0,
            'absent' => 0,
            'late' => 0,
        ];
    }
}
