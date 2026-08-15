<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    protected $table = 'hrm_api_tokens';

    protected $fillable = [
        'name',
        'token_prefix',
        'token_hash',
        'abilities',
        'created_by',
        'last_used_at',
        'expires_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function isActive(): bool
    {
        if ($this->revoked_at) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function can(string $ability): bool
    {
        $abilities = $this->abilities ?? ['*'];

        if (in_array('*', $abilities, true)) {
            return true;
        }

        if (in_array($ability, $abilities, true)) {
            return true;
        }

        // Write tokens can also read employees.
        if ($ability === 'employees:read' && in_array('employees:write', $abilities, true)) {
            return true;
        }

        return false;
    }

    /**
     * @return array{token:self,plain_text:string}
     */
    public static function issue(string $name, int $createdBy, array $abilities = ['*'], ?\DateTimeInterface $expiresAt = null): array
    {
        $plain = 'hrm_'.Str::random(48);

        $token = static::query()->create([
            'name' => $name,
            'token_prefix' => substr($plain, 0, 12),
            'token_hash' => hash('sha256', $plain),
            'abilities' => $abilities === [] ? ['*'] : array_values($abilities),
            'created_by' => $createdBy,
            'expires_at' => $expiresAt,
        ]);

        return ['token' => $token, 'plain_text' => $plain];
    }

    public static function findActiveByPlainText(string $plain): ?self
    {
        $plain = trim($plain);
        if ($plain === '') {
            return null;
        }

        $token = static::query()
            ->where('token_hash', hash('sha256', $plain))
            ->first();

        if (! $token || ! $token->isActive()) {
            return null;
        }

        return $token;
    }

    public function touchLastUsed(): void
    {
        $this->forceFill(['last_used_at' => now()])->save();
    }
}
