<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class EmailConfiguration extends Model
{
    protected $table = 'hrm_email_configurations';

    protected $fillable = [
        'config_key',
        'config_value',
        'description',
    ];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $all = static::allCached();
        $value = $all[$key] ?? $default;

        if ($key === 'SMTP_PASSWORD' && is_string($value) && $value !== '') {
            return static::decryptSecret($value);
        }

        return $value;
    }

    public static function setValue(string $key, string $value, ?string $description = null): void
    {
        if ($key === 'SMTP_PASSWORD' && $value !== '') {
            $value = static::encryptSecret($value);
        }

        static::query()->updateOrCreate(
            ['config_key' => $key],
            array_filter([
                'config_value' => $value,
                'description' => $description,
            ], fn ($v) => $v !== null)
        );

        Cache::forget('hrm_email_configurations');
    }

    public static function allCached(): array
    {
        return Cache::remember('hrm_email_configurations', 60, function () {
            return static::query()->pluck('config_value', 'config_key')->toArray();
        });
    }

    private static function encryptSecret(string $value): string
    {
        if (\App\Casts\EncryptedPassword::isEncryptedPayload($value)) {
            return $value;
        }

        return \Illuminate\Support\Facades\Crypt::encryptString($value);
    }

    private static function decryptSecret(string $value): string
    {
        if (! \App\Casts\EncryptedPassword::isEncryptedPayload($value)) {
            return $value;
        }

        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }

    public static function recipients(string $key): array
    {
        $raw = (string) static::getValue($key, '');

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /**
     * Primary docs From + any extra official From emails.
     *
     * @return array<int, string>
     */
    public static function officialFromEmails(): array
    {
        $emails = [];

        foreach ([
            static::getValue('FROM_EMAIL'),
            static::getValue('DOCS_FROM_EMAIL'),
            ...static::recipients('OFFICIAL_FROM_EMAILS'),
        ] as $email) {
            $email = strtolower(trim((string) $email));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[$email] = $email;
            }
        }

        return array_values($emails);
    }

    public static function isOfficialFromEmail(string $email): bool
    {
        $email = strtolower(trim($email));

        return in_array($email, array_map('strtolower', static::officialFromEmails()), true);
    }

    /**
     * @return array{email:?string,name:?string}
     */
    public static function docsFrom(): array
    {
        $email = trim((string) static::getValue('DOCS_FROM_EMAIL', ''));
        $name = trim((string) static::getValue('DOCS_FROM_NAME', ''));

        if ($email === '') {
            $email = trim((string) static::getValue('FROM_EMAIL', static::getValue('SMTP_USERNAME')));
        }
        if ($name === '') {
            $name = trim((string) static::getValue('FROM_NAME', 'Leadforgrow HRM'));
        }

        return ['email' => $email ?: null, 'name' => $name ?: null];
    }
}
