<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class EmployeeOnboardingStep extends Model
{
    protected $table = 'employee_onboarding_steps';

    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'step_id',
        'status',
        'comment',
        'created_at',
        'update_date',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'created_at' => 'datetime',
            'update_date' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(OnboardingStep::class, 'step_id', 'step_id');
    }

    public function allStepFiles(): HasMany
    {
        return $this->hasMany(OnboardingFile::class, 'step_id', 'step_id');
    }

    public function filesForEmployee(): Collection
    {
        return $this->allStepFiles
            ->where('employee_id', (int) $this->employee_id)
            ->values();
    }
}
