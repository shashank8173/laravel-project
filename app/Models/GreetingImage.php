<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GreetingImage extends Model
{
    protected $table = 'hrm_greeting_images';

    protected $fillable = [
        'type',
        'title',
        'image_path',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function url(): ?string
    {
        if (! $this->image_path || ! Storage::disk('public')->exists($this->image_path)) {
            return null;
        }

        return url(Storage::disk('public')->url($this->image_path));
    }

    public function absolutePath(): ?string
    {
        if (! $this->image_path || ! Storage::disk('public')->exists($this->image_path)) {
            return null;
        }

        return Storage::disk('public')->path($this->image_path);
    }

    public static function randomActive(string $type): ?self
    {
        $images = static::query()
            ->where('type', $type)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($images->isEmpty()) {
            return null;
        }

        return $images->random();
    }

    public static function randomUrl(string $type): ?string
    {
        return static::randomActive($type)?->url();
    }
}
