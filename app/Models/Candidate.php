<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Candidate extends Model
{
    protected $table = 'hrm_candidates';

    protected $fillable = [
        'name',
        'email',
        'mobile',
        'position',
        'status',
        'notes',
        'resume_path',
        'resume_name',
    ];

    public function hasResume(): bool
    {
        return filled($this->resume_path);
    }

    public function deleteResumeFile(): void
    {
        if ($this->resume_path && Storage::disk('public')->exists($this->resume_path)) {
            Storage::disk('public')->delete($this->resume_path);
        }
    }
}
