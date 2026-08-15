<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveApplied;
use App\Models\OfficeTiming;
use App\Models\UserAttendance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class SalaryCalculationService
{
    public function __construct(private readonly OfficeTimingService $officeTiming)
    {
    }

    /**
     * Full legacy calculate-salary.php computation for an employee/month/year.
     *
     * @return array<string, mixed>
     */
    public function calculate(Employee $employee, int $month, int $year): array
    {
        $settings = $this->officeTiming->settings();
        $salary = (float) ($employee->salary ?: 0);

        $totalHolidays = $this->countWorkingDayHolidays($month, $year, $settings);
        $totalWorkingDays = $this->countWorkingDays($month, $year, $settings);
        $perDaySalary = $totalWorkingDays > 0 ? ($salary / $totalWorkingDays) : 0;

        $attendanceCount = UserAttendance::query()
            ->where('user_id', $employee->id)
            ->whereMonth('clock_in_time', $month)
            ->whereYear('clock_in_time', $year)
            ->whereRaw("TIME(clock_in_time) > '00:00:00'")
            ->count();

        $presentDays = $attendanceCount + $totalHolidays;

        $lateBreakdown = $this->lateBreakdown($employee->id, $month, $year);
        $normalLate = $lateBreakdown['normal_late'];
        $lateExtra = $lateBreakdown['late_extra'];
        $halfDayAll = $lateBreakdown['half_day'];
        $totalLate = $normalLate + $lateExtra;

        $normalFine = (float) ($settings['normal_fine'] ?? OfficeTiming::query()->value('normal_fine') ?? 0);
        $extraFine = (float) ($settings['extra_fine'] ?? 0);
        $halfDayFine = $perDaySalary / 2;

        $leaveDaysRaw = (float) (LeaveApplied::query()
            ->where('emp_id', $employee->id)
            ->whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->sum('no_of_days') ?: 0);
        // Legacy: total_leave_days - 1
        $leaveDays = $leaveDaysRaw - 1;

        $afterDeduction = $this->salaryAfterDeductions(
            $salary,
            $totalWorkingDays,
            $presentDays,
            $halfDayAll,
            $normalLate,
            $lateExtra,
            $normalFine,
            $extraFine,
            $halfDayFine,
            $perDaySalary
        );

        $basic = $salary * 0.40;
        $hra = $basic * 0.50;
        $medicalAllowance = 800;
        $conveyanceAllowance = 1200;
        $specialAllowance = $salary - ($basic + $medicalAllowance + $hra + $conveyanceAllowance);
        $totalAllowance = $basic + $medicalAllowance + $hra + $conveyanceAllowance + $specialAllowance;
        $totalDeduction = round($salary - $afterDeduction);
        $netPay = round($afterDeduction);
        $payInWords = $this->roundAndConvertToWords($afterDeduction);

        $effectivePresent = min($presentDays, $totalWorkingDays);
        $lop = max(0, $totalWorkingDays - ($effectivePresent + 1)); // legacy LOP formula

        return [
            'salary' => $salary,
            'total_holidays' => $totalHolidays,
            'total_working_days' => $totalWorkingDays,
            'present_days' => $presentDays,
            'normal_late' => $normalLate,
            'late_extra' => $lateExtra,
            'half_day_all' => $halfDayAll,
            'total_late' => $totalLate,
            'leave_days' => $leaveDays,
            'normal_fine' => $normalFine,
            'extra_fine' => $extraFine,
            'half_day_fine' => $halfDayFine,
            'per_day_salary' => $perDaySalary,
            'after_deduction' => $afterDeduction,
            'basic' => $basic,
            'hra' => $hra,
            'medical_allowance' => $medicalAllowance,
            'conveyance_allowance' => $conveyanceAllowance,
            'special_allowance' => $specialAllowance,
            'total_allowance' => $totalAllowance,
            'total_deduction' => $totalDeduction,
            'net_pay' => $netPay,
            'pay_in_words' => $payInWords,
            'lop' => $lop,
            'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
        ];
    }

    public function salaryAfterDeductions(
        float $salary,
        float $totalDays,
        float $presentDays,
        float $halfDayAll,
        float $normalLate,
        float $lateExtra,
        float $normalFine,
        float $extraFine,
        float $halfDayFine,
        float $perDaySalary
    ): float {
        if ($presentDays > $totalDays) {
            $presentDays = $totalDays;
        }

        $absentDay = $totalDays - $presentDays;
        $salaryDeductions = ($normalLate * $normalFine)
            + ($lateExtra * $extraFine)
            + ($halfDayAll * $halfDayFine)
            + ($absentDay * $perDaySalary);

        return $salary - $salaryDeductions;
    }

    public function numberToWords(int|float $num): string
    {
        $num = (int) $num;
        $belowTwenty = [
            'Zero', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
            'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen',
        ];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        $aboveThousand = ['', 'Thousand', 'Million', 'Billion'];

        if ($num === 0) {
            return 'Zero';
        }

        $helper = function (int $n) use (&$helper, $belowTwenty, $tens): string {
            if ($n < 20) {
                return $belowTwenty[$n];
            }
            if ($n < 100) {
                return $tens[intval($n / 10)].($n % 10 ? ' '.$belowTwenty[$n % 10] : '');
            }
            if ($n < 1000) {
                return $belowTwenty[intval($n / 100)].' Hundred'.($n % 100 ? ' '.$helper($n % 100) : '');
            }

            return '';
        };

        $result = '';
        $i = 0;
        while ($num > 0) {
            if ($num % 1000 != 0) {
                $result = $helper($num % 1000).($aboveThousand[$i] ? ' '.$aboveThousand[$i] : '').' '.$result;
            }
            $num = intval($num / 1000);
            $i++;
        }

        return trim($result);
    }

    public function roundAndConvertToWords(float $decimal): string
    {
        return $this->numberToWords(round($decimal));
    }

    private function countWorkingDays(int $month, int $year, array $settings): int
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        $total = 0;

        foreach (CarbonPeriod::create($start, $end) as $day) {
            if ($this->officeTiming->isWorkingDay($day, $settings)) {
                $total++;
            }
        }

        return $total;
    }

    private function countWorkingDayHolidays(int $month, int $year, array $settings): int
    {
        $count = 0;

        Holiday::query()->where('year', $year)->get(['date'])->each(function (Holiday $holiday) use (&$count, $month, $settings) {
            $raw = trim((string) $holiday->date);
            if ($raw === '') {
                return;
            }

            try {
                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $raw)) {
                    $date = Carbon::createFromFormat('d-m-Y', $raw);
                } else {
                    $date = Carbon::parse($raw);
                }
            } catch (\Throwable) {
                return;
            }

            if ((int) $date->month !== $month) {
                return;
            }

            if (! $this->officeTiming->isWorkingDay($date, $settings)) {
                return;
            }

            $count++;
        });

        return $count;
    }

    /**
     * @return array{normal_late:int,late_extra:int,half_day:int}
     */
    private function lateBreakdown(int $employeeId, int $month, int $year): array
    {
        $rows = UserAttendance::query()
            ->where('user_id', $employeeId)
            ->whereMonth('clock_in_time', $month)
            ->whereYear('clock_in_time', $year)
            ->where('late_status', '!=', 'On Time')
            ->get(['late_status', 'status_color']);

        $normalLate = 0;
        $lateExtra = 0;
        $halfDay = 0;

        foreach ($rows as $row) {
            $status = trim((string) $row->late_status);
            $color = strtolower(trim((string) $row->status_color));

            if ($status === 'Half Day') {
                $halfDay++;
                continue;
            }

            // Legacy color checks + status fallbacks for hex colors from update_attendance
            if ($status === 'Late' && in_array($color, ['orange', '#f65a03'], true)) {
                $normalLate++;
            } elseif ($status === 'Late' && in_array($color, ['red', '#f70000'], true)) {
                $lateExtra++;
            } elseif ($status === 'Late (Extra Late)') {
                $lateExtra++;
            } elseif ($status === 'Late' || $status === 'Late (Extra Fine)') {
                $normalLate++;
            }
        }

        return [
            'normal_late' => $normalLate,
            'late_extra' => $lateExtra,
            'half_day' => $halfDay,
        ];
    }
}
