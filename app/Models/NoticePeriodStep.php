<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NoticePeriodStep extends Model
{
    protected $table = 'notice_period_steps';

    protected $primaryKey = 'step_id';

    public $timestamps = false;

    protected $fillable = [
        'step_order',
        'step_name',
        'description',
        'created_at',
    ];

    public function getRouteKeyName(): string
    {
        return 'step_id';
    }

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function employeeSteps(): HasMany
    {
        return $this->hasMany(EmployeeNoticePeriodStep::class, 'step_id', 'step_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(NoticePeriodFile::class, 'step_id', 'step_id');
    }
}
