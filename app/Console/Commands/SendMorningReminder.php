<?php

namespace App\Console\Commands;

use App\Models\EmailConfiguration;
use App\Models\Employee;
use App\Models\OfficeTiming;
use App\Services\HrmMailer;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendMorningReminder extends Command
{
    protected $signature = 'hrm:morning-reminder';

    protected $description = 'Send morning login reminder email to active employees';

    public function handle(HrmMailer $mailer): int
    {
        $timing = OfficeTiming::query()->first();
        $today = Carbon::today();

        if ($timing && ! $this->isWorkingDay($today, $timing->saturday_option ?? 'all-on')) {
            $this->info('Skipped (non-working day).');

            return self::SUCCESS;
        }

        $loginLabel = $timing?->login_time
            ? Carbon::parse($timing->login_time)->format('g:i A')
            : 'office start time';

        $cc = Employee::officialEmailsFor();

        $to = EmailConfiguration::recipients('EVENING_REMINDER_EMAIL')[0]
            ?? EmailConfiguration::getValue('FROM_EMAIL')
            ?? 'hr@example.com';

        $html = '<html><body><p>Dear Team,</p>'
            .'<p>This is a gentle reminder to log in to the system before <strong>'
            .e($loginLabel).'</strong>.</p>'
            .'<p>Best Regards,<br>HR</p></body></html>';

        $ok = $mailer->send($to, "Reminder: System Login Before {$loginLabel}", $html, $cc);

        $this->info($ok ? 'Morning reminder sent.' : 'Failed to send morning reminder.');

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    private function isWorkingDay(Carbon $date, string $saturdayOption): bool
    {
        if ($date->isSunday()) {
            return false;
        }

        if (! $date->isSaturday()) {
            return true;
        }

        return match ($saturdayOption) {
            'all-off' => false,
            '1st-3rd-on' => in_array((int) ceil($date->day / 7), [1, 3], true),
            default => true,
        };
    }
}
