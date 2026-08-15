<?php

namespace App\Console\Commands;

use App\Models\EmailConfiguration;
use App\Models\Employee;
use App\Models\OfficeTiming;
use App\Models\UserAttendance;
use App\Services\HrmMailer;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendEveningReminder extends Command
{
    protected $signature = 'hrm:evening-reminder';

    protected $description = 'Send evening punch-out reminder for employees still clocked in';

    public function handle(HrmMailer $mailer): int
    {
        $timing = OfficeTiming::query()->first();
        $today = Carbon::today();

        if ($timing && $today->isSunday()) {
            $this->info('Skipped (Sunday).');

            return self::SUCCESS;
        }

        $open = UserAttendance::query()
            ->whereDate('clock_in_time', $today)
            ->whereNull('clock_out_time')
            ->pluck('user_id');

        $emails = Employee::officialEmailsFor($open);

        $toList = EmailConfiguration::recipients('EVENING_REMINDER_EMAIL');
        $to = $toList[0] ?? EmailConfiguration::getValue('FROM_EMAIL');

        if (! $to) {
            $this->error('No EVENING_REMINDER_EMAIL configured.');

            return self::FAILURE;
        }

        $html = '<html><body><p>Dear Team,</p>'
            .'<p>Please remember to punch out before leaving for the day.</p>'
            .'<p>Employees still clocked in: <strong>'.count($emails).'</strong></p>'
            .'<p>Best Regards,<br>HR</p></body></html>';

        $ok = $mailer->send($to, 'Evening Reminder: Punch Out', $html, $emails);

        $this->info($ok ? 'Evening reminder sent.' : 'Failed to send evening reminder.');

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
