<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;

/**
 * Supports encrypted (reversible), legacy plaintext, and bcrypt/argon hashes.
 */
class LegacyEmployeeProvider extends EloquentUserProvider
{
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $plain = (string) ($credentials['password'] ?? '');
        // Model cast decrypts EncryptedPassword → plaintext for compare.
        $stored = (string) $user->getAuthPassword();

        if ($stored === '' || $plain === '') {
            return false;
        }

        // Only use Hash::check for real framework hashes (avoids RuntimeException on plaintext).
        if ($this->isHashedPassword($stored)) {
            return Hash::check($plain, $stored);
        }

        // Encrypted (decrypted by cast) or legacy plaintext.
        return hash_equals($stored, $plain);
    }

    private function isHashedPassword(string $value): bool
    {
        // bcrypt ($2y$/$2a$/$2b$), argon2i, argon2id
        return (bool) preg_match('/^\$2[ayb]\$|^\$(argon2id|argon2i)\$/', $value);
    }
}
