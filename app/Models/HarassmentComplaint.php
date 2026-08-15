<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HarassmentComplaint extends Model
{
    protected $table = 'sexual_harassment_complaints';

    public $timestamps = false;

    protected $fillable = [
        'emp_id',
        'complainant_contact',
        'complainant_department',
        'incident_date',
        'incident_location',
        'harasser_details',
        'incident_description',
        'witness_details',
        'evidence_path',
        'submission_date',
        'alleged_harasser_id',
        'complainant_designation',
        'complainant_name',
    ];

    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'submission_date' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }
}
