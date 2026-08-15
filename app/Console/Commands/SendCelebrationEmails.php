<?php

namespace App\Console\Commands;

use App\Services\CelebrationNotifier;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendCelebrationEmails extends Command
{
    protected $signature = 'hrm:celebrations {--date= : Run for Y-m-d (default: today)} {--dry-run : List only, do not send}';

    protected $description = 'Send birthday & work anniversary greetings + team reminders';

    public function handle(CelebrationNotifier $notifier): int
    {
        $date = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))->startOfDay()
            : Carbon::today();

        $dry = (bool) $this->option('dry-run');
        $result = $notifier->run($date, $dry);

        foreach ($result['birthdays'] as $row) {
            $this->line("Birthday: {$row['name']} <{$row['email']}>");
        }
        foreach ($result['anniversaries'] as $row) {
            $this->line("Anniversary: {$row['name']} <{$row['email']}>");
        }
        foreach ($result['holidays'] as $row) {
            $this->line("Holiday: {$row['name']}".(! empty($row['date']) ? " ({$row['date']})" : ''));
        }

        $this->info($result['message']);

        if ($result['birthdays'] === [] && $result['anniversaries'] === [] && $result['holidays'] === []) {
            $this->comment('No birthdays, anniversaries, or holidays for this date.');
        }

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
