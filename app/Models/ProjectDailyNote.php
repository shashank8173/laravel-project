<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDailyNote extends Model
{
    protected $table = 'hrm_project_daily_notes';

    public const WORK_STATUSES = [
        'pending',
        'working',
        'in_progress',
        'completed',
    ];

    protected $fillable = [
        'project_id',
        'employee_id',
        'note_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'note',
        'work_status',
        'progress_percent',
    ];

    protected function casts(): array
    {
        return [
            'note_date' => 'date',
            'progress_percent' => 'integer',
            'duration_minutes' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function statusLabel(): string
    {
        return match ($this->work_status) {
            'pending' => 'Pending',
            'working' => 'Working',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            default => ucwords(str_replace('_', ' ', (string) $this->work_status)),
        };
    }

    public function statusClass(): string
    {
        return match ($this->work_status) {
            'pending' => 'st-not_started',
            'working' => 'st-planning',
            'in_progress' => 'st-in_progress',
            'completed' => 'st-completed',
            default => 'st-not_started',
        };
    }

    public static function minutesBetween(?string $start, ?string $end): ?int
    {
        if (! $start || ! $end) {
            return null;
        }

        try {
            $s = Carbon::createFromFormat('H:i', substr($start, 0, 5));
            $e = Carbon::createFromFormat('H:i', substr($end, 0, 5));
        } catch (\Throwable) {
            return null;
        }

        if ($e->lt($s)) {
            // overnight shift
            $e->addDay();
        }

        return max(0, $s->diffInMinutes($e));
    }

    public function timeRangeLabel(): string
    {
        $start = $this->formatTime($this->start_time);
        $end = $this->formatTime($this->end_time);
        if (! $start && ! $end) {
            return '—';
        }

        return ($start ?: '?').' – '.($end ?: '?');
    }

    public function durationLabel(): string
    {
        $mins = $this->duration_minutes;
        if ($mins === null) {
            return '—';
        }
        $h = intdiv((int) $mins, 60);
        $m = ((int) $mins) % 60;
        if ($h > 0 && $m > 0) {
            return $h.'h '.$m.'m';
        }
        if ($h > 0) {
            return $h.'h';
        }

        return $m.'m';
    }

    private function formatTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $str = (string) $value;
        // DB time may be H:i:s
        if (preg_match('/^(\d{1,2}):(\d{2})/', $str, $m)) {
            $h = (int) $m[1];
            $min = (int) $m[2];
            $ampm = $h >= 12 ? 'PM' : 'AM';
            $h12 = $h % 12;
            if ($h12 === 0) {
                $h12 = 12;
            }

            return sprintf('%d:%02d %s', $h12, $min, $ampm);
        }

        return $str;
    }
}
