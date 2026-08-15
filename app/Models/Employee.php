<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Employee extends Authenticatable
{
    use Notifiable;

    protected $table = 'hrm_employee';

    public $timestamps = false;

    protected $fillable = [
        'fname',
        'lname',
        'email',
        'office_email',
        'mobile1',
        'mobile2',
        'password',
        'designation_id',
        'department_id',
        'role',
        'ui_theme',
        'notify_chat_sound',
        'notify_call_ringtone',
        'notify_app_sound',
        'status',
        'archive_status',
        'image',
        'emp_id',
        'external_id',
        'job_title',
        'salary',
        'doj',
        'dob',
        'gender',
        'bgroup',
        'marital_status',
        'fathers_name',
        'current_address',
        'permanent_address',
        'city_id',
        'state_id',
        'pincode',
        'attendance_id',
        'employee_type',
        'work_location',
        'probation_period',
        'probation_status',
        'experience',
        'religion',
        'nationality',
        'added_date',
        'update_date',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'datetime',
            'doj' => 'datetime',
            'status' => 'integer',
            'archive_status' => 'integer',
            'notify_chat_sound' => 'boolean',
            'notify_call_ringtone' => 'boolean',
            'notify_app_sound' => 'boolean',
            'password' => \App\Casts\EncryptedPassword::class,
        ];
    }

    /**
     * Plaintext password for admin display / login compare (auto-decrypted by cast).
     */
    public function plainPassword(): ?string
    {
        $value = $this->password;
        if ($value === null || $value === '') {
            return null;
        }

        // One-way hashes cannot be shown as plaintext.
        if (\App\Casts\EncryptedPassword::isHashedPassword((string) $value)) {
            return null;
        }

        return (string) $value;
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Official work email used for login and all outbound employee notifications.
     * Personal email (email) is never used for sending.
     */
    public function officialEmail(): ?string
    {
        $office = trim((string) ($this->office_email ?? ''));
        if ($office !== '' && filter_var($office, FILTER_VALIDATE_EMAIL)) {
            return $office;
        }

        return null;
    }

    /**
     * Laravel notification channel — always official email only.
     */
    public function routeNotificationForMail(): ?string
    {
        return $this->officialEmail();
    }

    /**
     * @param  iterable<int>|null  $ids
     * @return list<string>
     */
    public static function officialEmailsFor(?iterable $ids = null): array
    {
        $query = static::query()
            ->where('status', 1)
            ->where('archive_status', 0)
            ->whereNotNull('office_email')
            ->where('office_email', '!=', '');

        if ($ids !== null) {
            $query->whereIn('id', $ids);
        }

        return $query
            ->get(['office_email'])
            ->map(fn (self $e) => $e->officialEmail())
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->fname ?? '').' '.($this->lname ?? ''));
    }

    public function isAdmin(): bool
    {
        $role = strtolower(trim((string) $this->role));

        return in_array($role, ['admin', 'super admin'], true);
    }

    public function isSuperAdmin(): bool
    {
        return strtolower(trim((string) $this->role)) === 'super admin';
    }

    public function isHrDepartment(): bool
    {
        return in_array((int) $this->department_id, [4, 6], true);
    }

    public function isReportingManager(): bool
    {
        return ReportingManager::query()
            ->where('reporting_manager_id', $this->id)
            ->exists();
    }

    public function canManageTeam(): bool
    {
        return $this->isAdmin() || $this->isReportingManager();
    }

    /**
     * Admin dashboard + HR ops screens shared by admins and HR departments.
     */
    public function canAccessAdminDashboard(): bool
    {
        return $this->isAdmin() || $this->isHrDepartment();
    }

    /**
     * Employee IDs reporting to this user (empty for non-managers).
     *
     * @return list<int>
     */
    public function teamEmployeeIds(): array
    {
        return ReportingManager::query()
            ->where('reporting_manager_id', $this->id)
            ->pluck('employee_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function getProfileImageUrlAttribute(): string
    {
        $image = trim((string) $this->image);
        if ($image === '') {
            return asset('assets/img/profiles/avatar-02.jpg');
        }

        // Absolute / full URL stored
        if (preg_match('#^https?://#i', $image) || str_starts_with($image, '//')) {
            return $image;
        }

        $normalized = ltrim(str_replace('\\', '/', $image), '/');

        $candidates = [
            public_path('upload-image/'.$normalized),
            public_path('upload-image/'.basename($normalized)),
            public_path('assets/img/profiles/'.$normalized),
            public_path('assets/img/profiles/'.basename($normalized)),
            public_path($normalized),
            storage_path('app/public/'.$normalized),
        ];

        foreach ($candidates as $file) {
            if (is_file($file)) {
                if (str_starts_with($file, storage_path('app/public'))) {
                    return \Illuminate\Support\Facades\Storage::disk('public')->url(
                        ltrim(str_replace('\\', '/', substr($file, strlen(storage_path('app/public')))), '/')
                    );
                }

                $relative = ltrim(str_replace('\\', '/', str_replace(public_path(), '', $file)), '/');

                return asset($relative);
            }
        }

        return asset('assets/img/profiles/avatar-02.jpg');
    }

    /**
     * Store uploaded profile photo under public/upload-image (legacy-compatible).
     */
    public function storeProfileImage(\Illuminate\Http\UploadedFile $file): string
    {
        $dir = public_path('upload-image');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $old = trim((string) $this->image);
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $ext = 'jpg';
        }

        $filename = 'emp_'.$this->id.'_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
        $file->move($dir, $filename);

        $this->image = $filename;
        $this->save();

        // Remove previous custom upload (not default assets)
        if ($old !== '' && $old !== $filename && ! preg_match('#^https?://#i', $old)) {
            $oldBase = basename($old);
            $oldPath = public_path('upload-image/'.$oldBase);
            if (is_file($oldPath) && ! str_contains($oldBase, 'avatar-')) {
                @unlink($oldPath);
            }
        }

        return $filename;
    }

    public function isActive(): bool
    {
        return (int) $this->status === 1 && (int) $this->archive_status === 0;
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(LeaveApplied::class, 'emp_id');
    }

    public function bankDetail(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(BankDetail::class, 'emp_id');
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(EmployeeFamily::class, 'emp_id');
    }

    public function educations(): HasMany
    {
        return $this->hasMany(EmployeeEducation::class, 'emp_id');
    }

    public function projects(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'hrm_project_employee', 'employee_id', 'project_id')
            ->withPivot(['id', 'assigned_by', 'assigned_at', 'status'])
            ->withTimestamps();
    }

    public function managedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'project_manager_id');
    }
}
