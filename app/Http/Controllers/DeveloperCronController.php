<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Holiday;
use App\Services\CelebrationNotifier;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeveloperCronController extends Controller
{
    public function index(): View
    {
        $basePath = base_path();
        $phpBinary = PHP_BINARY ?: 'php';
        $scheduleCmd = $phpBinary.' '.escapeshellarg($basePath.DIRECTORY_SEPARATOR.'artisan').' schedule:run';
        $celebrationCmd = $phpBinary.' '.escapeshellarg($basePath.DIRECTORY_SEPARATOR.'artisan').' hrm:celebrations';
        $linuxCron = '* * * * * cd '.escapeshellarg($basePath).' && '.$phpBinary.' artisan schedule:run >> /dev/null 2>&1';
        $windowsTask = 'schtasks /Create /TN "HRMScheduler" /TR "'.$phpBinary.' '.$basePath.'\\artisan schedule:run" /SC MINUTE /MO 1 /F';

        $previewUrl = route('developer.cron.celebrations.preview');
        $sendUrl = route('developer.cron.celebrations.send');
        $forceUrl = route('developer.cron.celebrations.force');
        $holidayPreviewUrl = route('developer.cron.holidays.preview');
        $holidaySendUrl = route('developer.cron.holidays.send');
        $holidayForceUrl = route('developer.cron.holidays.force');

        $employees = Employee::query()
            ->with(['department:id,name', 'designation:id,name'])
            ->where('status', 1)
            ->where('archive_status', 0)
            ->whereNotNull('office_email')
            ->where('office_email', '!=', '')
            ->orderBy('fname')
            ->get(['id', 'fname', 'lname', 'email', 'office_email', 'department_id', 'designation_id', 'emp_id']);

        $holidays = Holiday::query()
            ->orderByDesc('year')
            ->orderBy('date')
            ->limit(200)
            ->get(['id', 'name', 'date', 'year']);

        return view('developer.cron-jobs', compact(
            'basePath',
            'phpBinary',
            'scheduleCmd',
            'celebrationCmd',
            'linuxCron',
            'windowsTask',
            'previewUrl',
            'sendUrl',
            'forceUrl',
            'holidayPreviewUrl',
            'holidaySendUrl',
            'holidayForceUrl',
            'employees',
            'holidays'
        ));
    }

    public function previewCelebrations(Request $request, CelebrationNotifier $notifier): JsonResponse|View
    {
        @set_time_limit(180);

        return $this->renderCelebrationResult($request, $notifier, true, 'preview');
    }

    public function sendCelebrations(Request $request, CelebrationNotifier $notifier): JsonResponse|View
    {
        @set_time_limit(180);

        return $this->renderCelebrationResult($request, $notifier, false, 'send');
    }

    public function forceCelebrations(Request $request, CelebrationNotifier $notifier): JsonResponse|View
    {
        @set_time_limit(180);

        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:hrm_employee,id'],
            'type' => ['nullable', 'in:birthday,anniversary,both'],
            'notify_team' => ['nullable', 'boolean'],
        ]);

        $employee = Employee::query()->findOrFail($data['employee_id']);
        $type = $data['type'] ?? 'both';
        $types = match ($type) {
            'birthday' => ['birthday'],
            'anniversary' => ['anniversary'],
            default => ['birthday', 'anniversary'],
        };

        $notifyTeam = $request->has('notify_team')
            ? $request->boolean('notify_team')
            : true;
        $result = $notifier->sendForcedTest($employee, $types, $notifyTeam);

        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json($result);
        }

        return view('developer.celebration-test-result', [
            'mode' => 'force',
            'result' => [
                'date' => now()->toDateString(),
                'dry_run' => false,
                'birthdays' => in_array('birthday', $types, true)
                    ? [['id' => $result['employee']['id'], 'name' => $result['employee']['name'], 'email' => $result['employee']['email']]]
                    : [],
                'anniversaries' => in_array('anniversary', $types, true)
                    ? [['id' => $result['employee']['id'], 'name' => $result['employee']['name'], 'email' => $result['employee']['email']]]
                    : [],
                'holidays' => [],
                'sent' => $result['sent'],
                'failed' => $result['failed'],
                'message' => $result['message'],
            ],
            'previewUrl' => route('developer.cron.celebrations.preview'),
            'sendUrl' => route('developer.cron.celebrations.send'),
        ]);
    }

    /**
     * Preview holidays for a date (no send).
     * /cron-jobs/test-holidays?date=2026-08-15
     */
    public function previewHolidays(Request $request, CelebrationNotifier $notifier): JsonResponse|View
    {
        $date = $this->resolveDate($request);
        $matched = $notifier->holidaysForDate($date)->map(fn (Holiday $h) => [
            'id' => (int) $h->id,
            'name' => (string) $h->name,
            'date' => $notifier->parseHolidayDate($h->date)?->toDateString(),
        ])->values()->all();

        $result = [
            'date' => $date->toDateString(),
            'dry_run' => true,
            'birthdays' => [],
            'anniversaries' => [],
            'holidays' => $matched,
            'sent' => 0,
            'failed' => 0,
            'message' => sprintf(
                'Holiday preview for %s | Matches: %d (no email sent)',
                $date->toDateString(),
                count($matched)
            ),
        ];

        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json($result);
        }

        return view('developer.celebration-test-result', [
            'mode' => 'holiday-preview',
            'result' => $result,
            'previewUrl' => route('developer.cron.holidays.preview', array_filter(['date' => $request->query('date')])),
            'sendUrl' => route('developer.cron.holidays.send', array_filter(['date' => $request->query('date')])),
        ]);
    }

    /**
     * Send holiday cards for date matches to all official emails.
     * /cron-jobs/test-holidays/send?date=2026-08-15
     */
    public function sendHolidays(Request $request, CelebrationNotifier $notifier): JsonResponse|View
    {
        @set_time_limit(180);

        return $this->sendHolidaysOnly($request, $notifier, $this->resolveDate($request));
    }

    public function forceHoliday(Request $request, CelebrationNotifier $notifier): JsonResponse|View
    {
        @set_time_limit(180);
        $data = $request->validate([
            'holiday_id' => ['required', 'integer', 'exists:hrm_holidays,id'],
        ]);

        $holiday = Holiday::query()->findOrFail($data['holiday_id']);
        $forced = $notifier->sendForcedHoliday($holiday);

        $result = [
            'date' => now()->toDateString(),
            'dry_run' => false,
            'birthdays' => [],
            'anniversaries' => [],
            'holidays' => [['id' => $forced['holiday']['id'], 'name' => $forced['holiday']['name'], 'date' => null]],
            'sent' => $forced['sent'],
            'failed' => $forced['failed'],
            'message' => $forced['message'],
        ];

        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json($result);
        }

        return view('developer.celebration-test-result', [
            'mode' => 'holiday-force',
            'result' => $result,
            'previewUrl' => route('developer.cron.holidays.preview'),
            'sendUrl' => route('developer.cron.holidays.send'),
        ]);
    }

    private function sendHolidaysOnly(Request $request, CelebrationNotifier $notifier, Carbon $date): JsonResponse|View
    {
        $settings = \App\Models\GreetingSetting::current();
        $matched = $notifier->holidaysForDate($date);
        $sent = 0;
        $failed = 0;
        $rows = [];

        if ($settings->holiday_enabled !== false) {
            foreach ($matched as $holiday) {
                $forced = $notifier->sendForcedHoliday($holiday);
                $sent += $forced['sent'];
                $failed += $forced['failed'];
                $rows[] = [
                    'id' => (int) $holiday->id,
                    'name' => (string) $holiday->name,
                    'date' => $notifier->parseHolidayDate($holiday->date)?->toDateString(),
                ];
            }
        }

        $result = [
            'date' => $date->toDateString(),
            'dry_run' => false,
            'birthdays' => [],
            'anniversaries' => [],
            'holidays' => $rows,
            'sent' => $sent,
            'failed' => $failed,
            'message' => sprintf(
                'Holiday send for %s | Holidays: %d | Sent: %d | Failed: %d (official emails)',
                $date->toDateString(),
                count($rows),
                $sent,
                $failed
            ),
        ];

        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json($result);
        }

        return view('developer.celebration-test-result', [
            'mode' => 'holiday-send',
            'result' => $result,
            'previewUrl' => route('developer.cron.holidays.preview', array_filter(['date' => $request->query('date')])),
            'sendUrl' => route('developer.cron.holidays.send', array_filter(['date' => $request->query('date')])),
        ]);
    }

    private function renderCelebrationResult(
        Request $request,
        CelebrationNotifier $notifier,
        bool $dryRun,
        string $mode
    ): JsonResponse|View {
        $date = $this->resolveDate($request);
        $result = $notifier->run($date, $dryRun);

        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json($result);
        }

        return view('developer.celebration-test-result', [
            'mode' => $mode,
            'result' => $result,
            'previewUrl' => route('developer.cron.celebrations.preview', array_filter(['date' => $request->query('date')])),
            'sendUrl' => route('developer.cron.celebrations.send', array_filter(['date' => $request->query('date')])),
        ]);
    }

    private function resolveDate(Request $request): Carbon
    {
        $raw = trim((string) $request->query('date', ''));
        if ($raw === '') {
            return Carbon::today();
        }

        return Carbon::parse($raw)->startOfDay();
    }
}
