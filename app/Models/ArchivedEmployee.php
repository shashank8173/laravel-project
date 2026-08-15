<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ArchivedEmployee extends Model
{
    protected $table = 'archived_employees';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'dob' => 'datetime',
            'doj' => 'datetime',
            'created_at' => 'datetime',
            'salary' => 'float',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->fname ?? '').' '.($this->lname ?? ''));
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }

    public function bankDetail(): HasOne
    {
        // Bank rows stay keyed by original employee id
        return $this->hasOne(BankDetail::class, 'emp_id', 'id');
    }

    public function getProfileImageUrlAttribute(): string
    {
        $image = trim((string) ($this->image ?? ''));
        if ($image === '') {
            return asset('assets/img/profiles/avatar-02.jpg');
        }

        $image = ltrim(str_replace('\\', '/', $image), '/');
        $candidates = [
            public_path($image),
            public_path('upload-image/'.basename($image)),
            public_path('assets/img/profiles/'.basename($image)),
            base_path('../hrmpulse_live-main/'.$image),
            base_path('../hrmpulse_live-main/upload-image/'.basename($image)),
        ];

        foreach ($candidates as $file) {
            if (is_file($file)) {
                if (str_starts_with($file, public_path())) {
                    return asset(ltrim(str_replace('\\', '/', str_replace(public_path(), '', $file)), '/'));
                }
                $targetDir = public_path('upload-image');
                if (! is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                $target = $targetDir.DIRECTORY_SEPARATOR.basename($file);
                if (! is_file($target)) {
                    @copy($file, $target);
                }
                if (is_file($target)) {
                    return asset('upload-image/'.basename($file));
                }
            }
        }

        return asset('assets/img/profiles/avatar-02.jpg');
    }

    /**
     * Hydrate an Employee model so salary calculator / payslip views work unchanged.
     */
    public function toEmployee(): Employee
    {
        $employee = new Employee;
        $attrs = $this->getAttributes();
        unset($attrs['created_at']);
        $employee->forceFill($attrs);
        $employee->id = $this->id;
        $employee->exists = true;

        if ($this->relationLoaded('designation')) {
            $employee->setRelation('designation', $this->designation);
        }
        if ($this->relationLoaded('department')) {
            $employee->setRelation('department', $this->department);
        }
        if ($this->relationLoaded('bankDetail')) {
            $employee->setRelation('bankDetail', $this->bankDetail);
        }

        return $employee;
    }
}
