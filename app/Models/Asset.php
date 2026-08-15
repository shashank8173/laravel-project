<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    protected $table = 'hrm_assets';

    public $timestamps = false;

    protected $fillable = ['image', 'quantity', 'asset_name', 'asset_id'];

    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class, 'asset_id');
    }

    public function openAssignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class, 'asset_id')->whereNull('return_date');
    }

    public function isAvailable(): bool
    {
        return ! $this->openAssignments()->exists();
    }

    public function imageUrl(): ?string
    {
        $path = trim((string) $this->image);
        if ($path === '') {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');

        $candidates = [
            public_path($path),
            public_path('upload-image/'.basename($path)),
            public_path('assets/upload-image/'.basename($path)),
            base_path('../hrmpulse_live-main/'.$path),
            base_path('../hrmpulse_live-main/upload-image/'.basename($path)),
        ];

        foreach ($candidates as $file) {
            if (is_file($file)) {
                if (str_starts_with($file, public_path())) {
                    return asset(ltrim(str_replace('\\', '/', str_replace(public_path(), '', $file)), '/'));
                }
                // Copy legacy into public once for serving
                $targetDir = public_path('assets/upload-image');
                if (! is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                $target = $targetDir.DIRECTORY_SEPARATOR.basename($file);
                if (! is_file($target)) {
                    @copy($file, $target);
                }
                if (is_file($target)) {
                    return asset('assets/upload-image/'.basename($file));
                }
            }
        }

        return asset($path);
    }
}
