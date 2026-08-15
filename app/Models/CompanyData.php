<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyData extends Model
{
    protected $table = 'company_data';

    public $timestamps = false;

    protected $fillable = [
        'document_name',
        'document_type',
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

    public function typeInitials(): string
    {
        $type = trim((string) $this->document_type);
        if ($type === '') {
            return 'CD';
        }

        return strtoupper(mb_substr($type, 0, 2));
    }
}
