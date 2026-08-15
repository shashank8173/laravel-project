<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class NoticePeriodFile extends Model
{
    protected $table = 'notice_period_files';

    public $timestamps = false;

    protected $fillable = [
        'step_id',
        'employee_id',
        'document_name',
        'file_path',
        'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(NoticePeriodStep::class, 'step_id', 'step_id');
    }

    public function absolutePath(): ?string
    {
        $path = ltrim(str_replace('\\', '/', (string) $this->file_path), '/');
        if ($path === '') {
            return null;
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->path($path);
        }

        $candidates = [
            public_path($path),
            base_path('../hrmpulse_live-main/'.$path),
            base_path('../hrmpulse_live-main/'.str_replace('Uploads/', 'Uploads/', $path)),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
