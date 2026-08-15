<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeEducation extends Model
{
    protected $table = 'hrm_employee_education';

    public $timestamps = false;

    protected $fillable = [
        'emp_id',
        'qualification_type',
        'course_name',
        'course_type',
        'stream',
        'start_date',
        'end_date',
        'college_name',
        'university_name',
        'grade',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }
}
