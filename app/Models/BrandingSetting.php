<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class BrandingSetting extends Model
{
    protected $table = 'branding_settings';

    protected $fillable = [
        'logo_path',
        'logo_url',
        'icon_path',
        'icon_url',
    ];

    public static function current(): self
    {
        return Cache::remember('branding_settings.current', 300, function () {
            $row = static::query()->first();
            if ($row) {
                return $row;
            }

            return static::query()->create([
                'logo_path' => null,
                'logo_url' => null,
                'icon_path' => null,
                'icon_url' => null,
            ]);
        });
    }

    public static function forgetCache(): void
    {
        Cache::forget('branding_settings.current');
    }

    public function logoUrl(): string
    {
        return $this->resolveAssetUrl($this->logo_url, $this->logo_path, 'assets/img/logo2.png');
    }

    public function iconUrl(): string
    {
        $resolved = $this->resolveAssetUrl($this->icon_url, $this->icon_path, null);
        if ($resolved) {
            return $resolved;
        }

        // Fall back to logo, then default logo file
        return $this->logoUrl();
    }

    public function logoAbsolutePath(): ?string
    {
        if ($this->logo_path && Storage::disk('public')->exists($this->logo_path)) {
            return Storage::disk('public')->path($this->logo_path);
        }

        $fallback = public_path('assets/img/logo2.png');

        return is_file($fallback) ? $fallback : null;
    }

    private function resolveAssetUrl(?string $url, ?string $path, ?string $fallbackAsset): ?string
    {
        $url = trim((string) $url);
        if ($url !== '') {
            if (preg_match('#^https?://#i', $url) || str_starts_with($url, '//')) {
                return $url;
            }

            // Relative path from public root
            return asset(ltrim($url, '/'));
        }

        $path = trim((string) $path);
        if ($path !== '') {
            $normalized = ltrim(str_replace('\\', '/', $path), '/');
            if (Storage::disk('public')->exists($normalized)) {
                return Storage::disk('public')->url($normalized);
            }
            if (is_file(public_path($normalized))) {
                return asset($normalized);
            }
        }

        if ($fallbackAsset) {
            $fallback = public_path($fallbackAsset);
            if (is_file($fallback)) {
                return asset($fallbackAsset);
            }

            return asset($fallbackAsset);
        }

        return null;
    }
}
