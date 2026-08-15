<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CompanyPolicy extends Model
{
    protected $table = 'company_policies';

    protected $fillable = [
        'policy_name',
        'policy_type',
        'updated_on',
        'file_path',
    ];

    protected function casts(): array
    {
        return ['updated_on' => 'date'];
    }

    public function fileName(): string
    {
        if (! $this->file_path) {
            return '—';
        }

        return basename(str_replace('\\', '/', $this->file_path));
    }

    public function hasFile(): bool
    {
        return filled($this->file_path);
    }

    public function isStoredOnPublicDisk(): bool
    {
        return $this->file_path && Storage::disk('public')->exists($this->file_path);
    }
}
