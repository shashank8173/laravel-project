<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    protected $table = 'companies';

    public $timestamps = false;

    protected $guarded = [];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'company_id');
    }

    public function logoUrl(): ?string
    {
        return $this->resolveImageUrl($this->logo);
    }

    public function bannerUrl(): ?string
    {
        return $this->resolveImageUrl($this->banner);
    }

    private function resolveImageUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        $normalized = ltrim(str_replace('\\', '/', $path), '/');

        if (Storage::disk('public')->exists($normalized)) {
            return Storage::disk('public')->url($normalized);
        }

        $publicCandidates = [
            public_path($normalized),
            public_path('uploads/'.$normalized),
            public_path('uploads/'.basename($normalized)),
            public_path('storage/'.$normalized),
        ];

        foreach ($publicCandidates as $file) {
            if (is_file($file)) {
                return asset(ltrim(str_replace(public_path(), '', $file), '/\\'));
            }
        }

        $legacyCandidates = [
            base_path('../hrmpulse_live-main/uploads/'.$normalized),
            base_path('../hrmpulse_live-main/uploads/'.basename($normalized)),
            base_path('../hrmpulse_live-main/'.$normalized),
        ];

        foreach ($legacyCandidates as $file) {
            if (is_file($file)) {
                // Copy once into Laravel public storage for reliable serving
                $target = 'company/legacy/'.basename($file);
                if (! Storage::disk('public')->exists($target)) {
                    Storage::disk('public')->put($target, file_get_contents($file));
                }

                return Storage::disk('public')->url($target);
            }
        }

        return null;
    }
}
