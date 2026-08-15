<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\GreetingSetting;
use App\Models\Holiday;
use Carbon\Carbon;

class CelebrationNotifier
{
    public function __construct(private HrmMailer $mailer) {}

    /**
     * Prefer official (office) email for all celebration notifications.
     */
    public static function officialEmail(Employee $employee): ?string
    {
        return $employee->officialEmail();
    }

    /**
     * @return list<string>
     */
    public function officialEmails(): array
    {
        return Employee::officialEmailsFor();
    }

    /**
     * @return array{
     *   date:string,
     *   dry_run:bool,
     *   birthdays:array<int, array{id:int,name:string,email:string}>,
     *   anniversaries:array<int, array{id:int,name:string,email:string}>,
     *   holidays:array<int, array{id:int,name:string,date:?string}>,
     *   sent:int,
     *   failed:int,
     *   message:string
     * }
     */
    public function run(?Carbon $date = null, bool $dryRun = true): array
    {
        $date = ($date ?? Carbon::today())->startOfDay();
        $settings = GreetingSetting::current();
        $builder = GreetingMailBuilder::make($settings);

        $month = (int) $date->format('m');
        $day = (int) $date->format('d');

        $employees = Employee::query()
            ->where('status', 1)
            ->where('archive_status', 0)
            ->whereNotNull('office_email')
            ->where('office_email', '!=', '')
            ->get(['id', 'fname', 'lname', 'email', 'office_email', 'dob', 'doj']);

        $allEmails = $employees
            ->map(fn (Employee $e) => self::officialEmail($e))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $birthdays = [];
        $anniversaries = [];
        $holidays = [];
        $sent = 0;
        $failed = 0;

        foreach ($employees as $employee) {
            $name = $employee->full_name;
            $email = self::officialEmail($employee);
            if (! $email) {
                continue;
            }

            $isBirthday = $settings->birthday_enabled
                && $employee->dob
                && (int) $employee->dob->format('m') === $month
                && (int) $employee->dob->format('d') === $day;

            $isAnniversary = $settings->anniversary_enabled
                && $employee->doj
                && (int) $employee->doj->format('m') === $month
                && (int) $employee->doj->format('d') === $day;

            if ($isBirthday) {
                $birthdays[] = ['id' => (int) $employee->id, 'name' => $name, 'email' => $email];
                if (! $dryRun) {
                    [$s, $f] = $this->sendBirthday($builder, $settings, $name, $email, $allEmails);
                    $sent += $s;
                    $failed += $f;
                }
            }

            if ($isAnniversary) {
                $anniversaries[] = ['id' => (int) $employee->id, 'name' => $name, 'email' => $email];
                if (! $dryRun) {
                    [$s, $f] = $this->sendAnniversary($builder, $settings, $name, $email, $allEmails);
                    $sent += $s;
                    $failed += $f;
                }
            }
        }

        if ($settings->holiday_enabled !== false) {
            foreach ($this->holidaysForDate($date) as $holiday) {
                $holidays[] = [
                    'id' => (int) $holiday->id,
                    'name' => (string) $holiday->name,
                    'date' => $this->parseHolidayDate($holiday->date)?->toDateString(),
                ];

                if (! $dryRun) {
                    [$s, $f] = $this->sendHoliday($builder, $settings, (string) $holiday->name, $allEmails);
                    $sent += $s;
                    $failed += $f;
                }
            }
        }

        $message = sprintf(
            'Date %s | Birthdays: %d | Anniversaries: %d | Holidays: %d | Sent: %d | Failed: %d%s (official emails only)',
            $date->toDateString(),
            count($birthdays),
            count($anniversaries),
            count($holidays),
            $sent,
            $failed,
            $dryRun ? ' (preview only — no email sent)' : ''
        );

        if (count($birthdays) === 0 && count($anniversaries) === 0 && count($holidays) === 0) {
            $message .= ' | No matches for this date.';
        }

        return [
            'date' => $date->toDateString(),
            'dry_run' => $dryRun,
            'birthdays' => $birthdays,
            'anniversaries' => $anniversaries,
            'holidays' => $holidays,
            'sent' => $sent,
            'failed' => $failed,
            'message' => $message,
        ];
    }

    /**
     * @param  array<int, string>  $types  birthday|anniversary
     * @return array{ok:bool,sent:int,failed:int,message:string,employee:array{id:int,name:string,email:string}}
     */
    public function sendForcedTest(Employee $employee, array $types = ['birthday', 'anniversary'], bool $notifyTeam = true): array
    {
        $settings = GreetingSetting::current();
        $builder = GreetingMailBuilder::make($settings);
        $name = $employee->full_name;
        $email = self::officialEmail($employee);

        if (! $email) {
            return [
                'ok' => false,
                'sent' => 0,
                'failed' => 0,
                'message' => 'Employee official (office) email missing/invalid. Set office_email first.',
                'employee' => ['id' => (int) $employee->id, 'name' => $name, 'email' => ''],
            ];
        }

        $allEmails = $notifyTeam ? $this->officialEmails() : [];
        $sent = 0;
        $failed = 0;
        $parts = [];

        if (in_array('birthday', $types, true)) {
            [$s, $f] = $this->sendBirthday($builder, $settings, $name, $email, $allEmails);
            $sent += $s;
            $failed += $f;
            $parts[] = 'birthday';
        }

        if (in_array('anniversary', $types, true)) {
            [$s, $f] = $this->sendAnniversary($builder, $settings, $name, $email, $allEmails);
            $sent += $s;
            $failed += $f;
            $parts[] = 'anniversary';
        }

        return [
            'ok' => $failed === 0 && $sent > 0,
            'sent' => $sent,
            'failed' => $failed,
            'message' => sprintf(
                'Forced test (%s) for %s <%s> | Sent: %d | Failed: %d',
                implode(' + ', $parts) ?: 'none',
                $name,
                $email,
                $sent,
                $failed
            ),
            'employee' => ['id' => (int) $employee->id, 'name' => $name, 'email' => $email],
        ];
    }

    /**
     * Force-send one holiday card to all official emails (manual URL test).
     *
     * @return array{ok:bool,sent:int,failed:int,message:string,holiday:array{id:int,name:string}}
     */
    public function sendForcedHoliday(Holiday $holiday): array
    {
        $settings = GreetingSetting::current();
        $builder = GreetingMailBuilder::make($settings);
        $emails = $this->officialEmails();
        $name = (string) $holiday->name;

        if ($emails === []) {
            return [
                'ok' => false,
                'sent' => 0,
                'failed' => 0,
                'message' => 'No official emails found to send holiday card.',
                'holiday' => ['id' => (int) $holiday->id, 'name' => $name],
            ];
        }

        [$sent, $failed] = $this->sendHoliday($builder, $settings, $name, $emails);

        return [
            'ok' => $failed === 0 && $sent > 0,
            'sent' => $sent,
            'failed' => $failed,
            'message' => sprintf(
                'Forced holiday test "%s" | Recipients: %d | Sent: %d | Failed: %d',
                $name,
                count($emails),
                $sent,
                $failed
            ),
            'holiday' => ['id' => (int) $holiday->id, 'name' => $name],
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, Holiday>
     */
    public function holidaysForDate(Carbon $date)
    {
        $month = (int) $date->format('m');
        $day = (int) $date->format('d');
        $year = (int) $date->format('Y');

        return Holiday::query()
            ->get(['id', 'name', 'date', 'year'])
            ->filter(function (Holiday $holiday) use ($month, $day, $year) {
                $parsed = $this->parseHolidayDate($holiday->date);
                if (! $parsed) {
                    return false;
                }

                if ((int) $parsed->format('m') !== $month || (int) $parsed->format('d') !== $day) {
                    return false;
                }

                // Prefer same calendar year from date string or year column when present.
                $dateYear = (int) $parsed->format('Y');
                $rowYear = (int) ($holiday->year ?? 0);
                if ($dateYear >= 2000 && $dateYear !== $year && $rowYear !== $year) {
                    return false;
                }

                return true;
            })
            ->values();
    }

    public function parseHolidayDate(?string $value): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            // Legacy holidays often store d-m-Y (e.g. 15-08-2026).
            if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $value)) {
                return Carbon::createFromFormat('d-m-Y', $value)->startOfDay();
            }

            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<int, string>  $allEmails
     * @return array{0:int,1:int}
     */
    private function sendBirthday(
        GreetingMailBuilder $builder,
        GreetingSetting $settings,
        string $name,
        string $email,
        array $allEmails
    ): array {
        $sent = 0;
        $failed = 0;

        $card = $builder->birthdayCard($name, $email);
        $this->dispatchCard($email, $settings->fillPlaceholders($settings->birthday_subject, $name, $email), $card)
            ? $sent++ : $failed++;

        if ($allEmails) {
            $alert = $builder->birthdayAlert($name, $email);
            $alertSubject = $settings->fillPlaceholders($settings->birthday_alert_subject, $name, $email);
            [$s, $f] = $this->dispatchCardBulk($allEmails, $alertSubject, $alert);
            $sent += $s;
            $failed += $f;
        }

        return [$sent, $failed];
    }

    /**
     * @param  array<int, string>  $allEmails
     * @return array{0:int,1:int}
     */
    private function sendAnniversary(
        GreetingMailBuilder $builder,
        GreetingSetting $settings,
        string $name,
        string $email,
        array $allEmails
    ): array {
        $sent = 0;
        $failed = 0;

        $card = $builder->anniversaryCard($name, $email);
        $this->dispatchCard($email, $settings->fillPlaceholders($settings->anniversary_subject, $name, $email), $card)
            ? $sent++ : $failed++;

        if ($allEmails) {
            $alert = $builder->anniversaryAlert($name, $email);
            $alertSubject = $settings->fillPlaceholders($settings->anniversary_alert_subject, $name, $email);
            [$s, $f] = $this->dispatchCardBulk($allEmails, $alertSubject, $alert);
            $sent += $s;
            $failed += $f;
        }

        return [$sent, $failed];
    }

    /**
     * @param  array<int, string>  $allEmails
     * @return array{0:int,1:int}
     */
    private function sendHoliday(
        GreetingMailBuilder $builder,
        GreetingSetting $settings,
        string $holidayName,
        array $allEmails
    ): array {
        $subject = $settings->fillPlaceholders((string) $settings->holiday_subject, '', '', $holidayName);
        $card = $builder->holidayCard($holidayName);

        return $this->dispatchCardBulk($allEmails, $subject, $card);
    }

    /**
     * @param  array{html:string,inline:array<int, array{path:string,cid:string,name:string}>}  $card
     */
    private function dispatchCard(string $to, string $subject, array $card): bool
    {
        return $this->mailer->send(
            $to,
            $subject,
            $card['html'],
            [],
            null,
            null,
            ['inline_images' => $card['inline'] ?? []]
        );
    }

    /**
     * One SMTP send per chunk: first address as To, rest as BCC (avoids timeout).
     *
     * @param  array<int, string>  $emails
     * @param  array{html:string,inline:array<int, array{path:string,cid:string,name:string}>}  $card
     * @return array{0:int,1:int}
     */
    private function dispatchCardBulk(array $emails, string $subject, array $card): array
    {
        $emails = array_values(array_unique(array_filter(
            array_map(fn ($e) => strtolower(trim((string) $e)), $emails),
            fn ($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL)
        )));

        if ($emails === []) {
            return [0, 0];
        }

        $sent = 0;
        $failed = 0;

        foreach (array_chunk($emails, 40) as $chunk) {
            $to = array_shift($chunk);
            $ok = $this->mailer->send(
                $to,
                $subject,
                $card['html'],
                [],
                null,
                null,
                [
                    'inline_images' => $card['inline'] ?? [],
                    'bcc' => $chunk,
                ]
            );

            $count = 1 + count($chunk);
            if ($ok) {
                $sent += $count;
            } else {
                $failed += $count;
            }
        }

        return [$sent, $failed];
    }
}
