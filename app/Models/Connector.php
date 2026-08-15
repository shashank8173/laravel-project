<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class Connector extends Model
{
    protected $table = 'hrm_connectors';

    protected $fillable = [
        'type',
        'slug',
        'name',
        'enabled',
        'webhook_secret',
        'config',
        'field_map',
        'last_sync_at',
        'last_sync_status',
        'last_sync_message',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'field_map' => 'array',
            'last_sync_at' => 'datetime',
        ];
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(ConnectorSyncLog::class, 'connector_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfigArrayAttribute(): array
    {
        $raw = $this->attributes['config'] ?? null;
        if (! $raw) {
            return [];
        }

        try {
            $json = Crypt::decryptString($raw);

            return json_decode($json, true) ?: [];
        } catch (\Throwable) {
            $decoded = json_decode((string) $raw, true);

            return is_array($decoded) ? $decoded : [];
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function setConfigArray(array $config): void
    {
        $this->attributes['config'] = Crypt::encryptString(json_encode($config));
    }

    public function ensureWebhookSecret(): string
    {
        if (! $this->webhook_secret) {
            $this->webhook_secret = 'whsec_'.Str::random(40);
            $this->save();
        }

        return (string) $this->webhook_secret;
    }

    public function ensureSlug(): string
    {
        if (! $this->slug) {
            $base = Str::slug($this->name ?: 'connector') ?: 'connector';
            $slug = $base;
            $i = 1;
            while (static::query()->where('slug', $slug)->where('id', '!=', $this->id)->exists()) {
                $slug = $base.'-'.$i;
                $i++;
            }
            $this->slug = $slug;
            $this->save();
        }

        return (string) $this->slug;
    }

    public function webhookUrl(): string
    {
        $slug = $this->slug ?: $this->id;

        return rtrim((string) config('app.url'), '/').'/api/v1/connectors/'.$slug.'/webhook';
    }
}
