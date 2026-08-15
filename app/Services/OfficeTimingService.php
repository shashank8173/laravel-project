<?php

namespace App\Services;

use App\Models\OfficeTiming;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class OfficeTimingService
{
    public function settings(): array
    {
        $defaults = [
            'relaxation_time' => '09:06:00',
            'normal_fine' => 0,
            'extra_fine_time' => '09:15:00',
            'extra_fine' => 0,
            'half_day_time' => '10:30:00',
            'evening_half_time' => '13:00:00',
            'login_time' => '09:00:00',
            'logout_time' => '18:30:00',
            'saturday_option' => 'all-on',
        ];

        $row = OfficeTiming::query()->find(1);
        if (! $row) {
            return $defaults;
        }

        $data = array_filter($row->toArray(), static fn ($value) => $value !== null && $value !== '');

        return array_merge($defaults, $data);
    }

    public function weekOfMonth(CarbonInterface|string $date): int
    {
        $dateObj = $date instanceof CarbonInterface ? $date : Carbon::parse($date);

        return (int) ceil(((int) $dateObj->format('j')) / 7);
    }

    public function isWorkingDay(CarbonInterface|string $date, ?array $officeTiming = null): bool
    {
        $officeTiming ??= $this->settings();
        $dateObj = $date instanceof CarbonInterface ? $date : Carbon::parse($date);
        $dayOfWeek = (int) $dateObj->format('N');

        if ($dayOfWeek === 7) {
            return false;
        }

        if ($dayOfWeek === 6) {
            $saturdayOption = $officeTiming['saturday_option'] ?? 'all-on';
            if ($saturdayOption === 'all-off') {
                return false;
            }
            if ($saturdayOption === '1st-3rd-on') {
                return in_array($this->weekOfMonth($dateObj), [1, 3], true);
            }
        }

        return true;
    }

    public function nonWorkingLabel(CarbonInterface|string $date, ?array $officeTiming = null): string
    {
        $officeTiming ??= $this->settings();
        $dateObj = $date instanceof CarbonInterface ? $date : Carbon::parse($date);
        $dayOfWeek = (int) $dateObj->format('N');

        if ($dayOfWeek === 7) {
            return 'Sunday';
        }

        if ($dayOfWeek === 6 && ! $this->isWorkingDay($dateObj, $officeTiming)) {
            return 'Saturday';
        }

        return '';
    }

    public function timeToTimestamp(string $date, string $time): int
    {
        return Carbon::parse($date.' '.$time)->getTimestamp();
    }

    public function workingSeconds(?array $officeTiming = null, ?string $date = null): int
    {
        $officeTiming ??= $this->settings();
        $date ??= now()->toDateString();
        $start = $this->timeToTimestamp($date, (string) $officeTiming['login_time']);
        $end = $this->timeToTimestamp($date, (string) $officeTiming['logout_time']);

        return max(0, $end - $start);
    }

    public function shortLeaveMinuteRange(?array $officeTiming = null): array
    {
        $officeTiming ??= $this->settings();
        $min = max(0, (strtotime($officeTiming['evening_half_time']) - strtotime($officeTiming['relaxation_time'])) / 60);
        $max = max($min, (strtotime($officeTiming['half_day_time']) - strtotime($officeTiming['relaxation_time'])) / 60);

        return [(int) $min, (int) $max];
    }

    /**
     * @return array{status:string,late_status:string,status_color:string,total_working_time:?string,extra_or_remaining_time:?string,extra_or_remaining_label:?string}
     */
    public function computePunchMetrics(string $date, string $clockIn, ?string $clockOut): array
    {
        $settings = $this->settings();
        $clockInTime = "{$date} {$clockIn}:00";
        $clockOutTime = $clockOut ? "{$date} {$clockOut}:00" : null;

        $loginTs = strtotime($clockInTime);
        $relaxation = $this->timeToTimestamp($date, (string) $settings['relaxation_time']);
        $extraFine = $this->timeToTimestamp($date, (string) $settings['extra_fine_time']);
        $halfDay = $this->timeToTimestamp($date, (string) $settings['half_day_time']);

        $lateStatus = 'On Time';
        $statusColor = '#05bb2c';

        if ($loginTs > $relaxation) {
            if ($loginTs <= $extraFine) {
                $lateStatus = 'Late';
                $statusColor = '#f65a03';
            } elseif ($loginTs <= $halfDay) {
                $lateStatus = 'Late (Extra Late)';
                $statusColor = '#f70000';
            } else {
                $lateStatus = 'Half Day';
                $statusColor = '#dc34ac';
            }
        }

        $totalWorkingTime = null;
        $extraOrRemainingTime = null;
        $extraOrRemainingLabel = null;

        if ($clockOutTime) {
            $logoutTs = strtotime($clockOutTime);
            $totalSeconds = max(0, $logoutTs - $loginTs);
            $totalWorkingTime = gmdate('H:i:s', $totalSeconds);
            $diff = $totalSeconds - $this->workingSeconds($settings, $date);
            $extraOrRemainingTime = gmdate('H:i:s', abs($diff));
            $extraOrRemainingLabel = $diff > 0 ? 'Extra Time' : 'Remaining Time';
        }

        return [
            'status' => 'present',
            'late_status' => $lateStatus,
            'status_color' => $statusColor,
            'total_working_time' => $totalWorkingTime,
            'extra_or_remaining_time' => $extraOrRemainingTime,
            'extra_or_remaining_label' => $extraOrRemainingLabel,
        ];
    }
}
