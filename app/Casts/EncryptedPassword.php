<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Reversible encryption for credentials that must still be viewable in admin.
 * Supports legacy plaintext and one-way bcrypt/argon hashes during migration.
 *
 * @implements CastsAttributes<string|null, string|null>
 */
class EncryptedPassword implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $value = (string) $value;

        if ($this->isHashedPassword($value)) {
            return $value;
        }

        if ($this->isEncryptedPayload($value)) {
            try {
                return Crypt::decryptString($value);
            } catch (Throwable) {
                return $value;
            }
        }

        // Legacy plaintext still in DB.
        return $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null || $value === '') {
            return [$key => $value];
        }

        $value = (string) $value;

        if ($this->isEncryptedPayload($value) || $this->isHashedPassword($value)) {
            return [$key => $value];
        }

        return [$key => Crypt::encryptString($value)];
    }

    public static function isEncryptedPayload(string $value): bool
    {
        // Laravel Crypt payloads are base64 JSON: {"iv":"...","value":"...","mac":"..."}
        if (! str_starts_with($value, 'eyJ')) {
            return false;
        }

        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return false;
        }

        $json = json_decode($decoded, true);

        return is_array($json)
            && isset($json['iv'], $json['value'], $json['mac']);
    }

    public static function isHashedPassword(string $value): bool
    {
        return (bool) preg_match('/^\$2[ayb]\$|^\$(argon2id|argon2i)\$/', $value);
    }
}
