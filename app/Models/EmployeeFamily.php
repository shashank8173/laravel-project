<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeFamily extends Model
{
    protected $table = 'hrm_employee_family';

    public $timestamps = false;

    protected $fillable = [
        'emp_id',
        'name',
        'relationship_id',
        'dependent',
        'phone',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }

    public function relationship(): BelongsTo
    {
        return $this->belongsTo(FamilyRelationship::class, 'relationship_id');
    }
}
