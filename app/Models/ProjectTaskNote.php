<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTaskNote extends Model
{
    protected $table = 'hrm_project_task_notes';

    protected $fillable = [
        'task_id',
        'project_id',
        'employee_id',
        'note_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'note',
        'work_status',
    ];

    protected function casts(): array
    {
        return [
            'note_date' => 'date',
            'duration_minutes' => 'integer',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'task_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
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

    public function statusLabel(): string
    {
        return match ($this->work_status) {
            'pending' => 'Pending',
            'working' => 'Working',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            default => $this->work_status ? ucwords(str_replace('_', ' ', $this->work_status)) : '—',
        };
    }

    private function formatTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $str = (string) $value;
        if (preg_match('/^(\d{1,2}):(\d{2})/', $str, $m)) {
            $h = (int) $m[1];
            $min = (int) $m[2];
            $ampm = $h >= 12 ? 'PM' : 'AM';
            $h12 = $h % 12 ?: 12;

            return sprintf('%d:%02d %s', $h12, $min, $ampm);
        }

        return $str;
    }

    public static function minutesBetween(?string $start, ?string $end): ?int
    {
        return ProjectDailyNote::minutesBetween($start, $end);
    }
}
