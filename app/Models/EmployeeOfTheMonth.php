<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeOfTheMonth extends Model
{
    protected $table = 'hrm_employee_of_the_month';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'designation',
        'message',
        'image_url',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function photoUrl(): ?string
    {
        $path = trim((string) $this->image_url);
        if ($path === '' || $path === 'default.png') {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');

        if (is_file(public_path($path))) {
            return asset($path);
        }

        if (is_file(public_path('assets/upload-image/'.basename($path)))) {
            return asset('assets/upload-image/'.basename($path));
        }

        $legacy = base_path('../hrmpulse_live-main/'.$path);
        if (is_file($legacy)) {
            return null; // keep null if not in Laravel public
        }

        return asset($path);
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : 'E';
    }
}
