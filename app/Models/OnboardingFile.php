<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingFile extends Model
{
    protected $table = 'onboarding_files';

    public $timestamps = false;

    protected $fillable = [
        'step_id',
        'employee_id',
        'document_name',
        'file_path',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(OnboardingStep::class, 'step_id', 'step_id');
    }

    public function url(): string
    {
        $path = ltrim(str_replace('\\', '/', (string) $this->file_path), '/');

        if (is_file(public_path($path))) {
            return asset($path);
        }

        $legacy = base_path('../hrmpulse_live-main/'.$path);
        if (is_file($legacy)) {
            $targetDir = public_path('uploads/onboarding');
            if (! is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            $target = $targetDir.DIRECTORY_SEPARATOR.basename($legacy);
            if (! is_file($target)) {
                @copy($legacy, $target);
            }
            if (is_file($target)) {
                return asset('uploads/onboarding/'.basename($legacy));
            }
        }

        return asset($path);
    }
}
