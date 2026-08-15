<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnboardingStep extends Model
{
    protected $table = 'onboarding_steps';

    protected $primaryKey = 'step_id';

    public $timestamps = false;

    protected $fillable = ['step_order', 'step_name', 'description'];

    public function employeeSteps(): HasMany
    {
        return $this->hasMany(EmployeeOnboardingStep::class, 'step_id', 'step_id');
    }
}
