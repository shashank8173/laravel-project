<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GreetingSetting extends Model
{
    protected $table = 'hrm_greeting_settings';

    protected $fillable = [
        'birthday_enabled',
        'birthday_subject',
        'birthday_heading',
        'birthday_message',
        'birthday_footer',
        'birthday_alert_subject',
        'birthday_alert_heading',
        'birthday_alert_message',
        'anniversary_enabled',
        'anniversary_subject',
        'anniversary_heading',
        'anniversary_message',
        'anniversary_footer',
        'anniversary_alert_subject',
        'anniversary_alert_heading',
        'anniversary_alert_message',
        'holiday_enabled',
        'holiday_subject',
        'holiday_heading',
        'holiday_message',
        'holiday_footer',
    ];

    protected function casts(): array
    {
        return [
            'birthday_enabled' => 'boolean',
            'anniversary_enabled' => 'boolean',
            'holiday_enabled' => 'boolean',
        ];
    }

    public static function defaults(): array
    {
        return [
            'birthday_enabled' => true,
            'birthday_subject' => 'Happy Birthday!',
            'birthday_heading' => 'Happy Birthday, {name}!',
            'birthday_message' => 'Wishing you a day filled with happiness, joy, and lots of cake!',
            'birthday_footer' => 'Best wishes from the HR Team!',
            'birthday_alert_subject' => 'Birthday Alert!',
            'birthday_alert_heading' => 'Birthday Alert!',
            'birthday_alert_message' => "Dear Team,\n\nIt's {name}'s Birthday today! Send your warm wishes to {email}.",
            'anniversary_enabled' => true,
            'anniversary_subject' => 'Happy Work Anniversary!',
            'anniversary_heading' => 'Congratulations, {name}!',
            'anniversary_message' => 'Thank you for your dedication and hard work. Here\'s to many more successful years!',
            'anniversary_footer' => 'Best regards, HR Team',
            'anniversary_alert_subject' => 'Work Anniversary Alert!',
            'anniversary_alert_heading' => 'Work Anniversary Alert!',
            'anniversary_alert_message' => "Dear Team,\n\nIt's {name}'s Work Anniversary today! Congratulate them at {email}.",
            'holiday_enabled' => true,
            'holiday_subject' => 'Happy {holiday}!',
            'holiday_heading' => 'Happy {holiday}!',
            'holiday_message' => 'Wishing you and your loved ones a wonderful celebration!',
            'holiday_footer' => 'Best wishes from the HR Team!',
        ];
    }

    public static function current(): self
    {
        $row = static::query()->first();
        if ($row) {
            return $row;
        }

        return static::query()->create(static::defaults());
    }

    public function fillPlaceholders(string $text, string $name = '', string $email = '', string $holiday = ''): string
    {
        return str_replace(
            ['{name}', '{email}', '{holiday}', '{NAME}', '{EMAIL}', '{HOLIDAY}'],
            [$name, $email, $holiday, $name, $email, $holiday],
            $text
        );
    }
}
