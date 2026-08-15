<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PayslipSetting extends Model
{
    protected $table = 'payslip_settings';

    protected $fillable = [
        'company_name',
        'company_address',
        'cin',
        'location',
        'logo_path',
        'signature_path',
        'signatory_name',
        'footer_note',
    ];

    public static function defaults(): array
    {
        return [
            'company_name' => 'Expetize Private Limited',
            'company_address' => "401, Vinayak Complex, Plot No 76, Vijay Block, Laxmi Nagar,\nNear Pillar No-51-52, Delhi, Delhi-110092",
            'cin' => 'U74999DL2016PTC307712',
            'location' => 'Delhi',
            'logo_path' => null,
            'signature_path' => null,
            'signatory_name' => 'Authorized Signatory',
            'footer_note' => 'This is a computer-generated salary slip. Please contact HR for any discrepancies.',
        ];
    }

    public static function current(): self
    {
        $row = static::query()->first();
        if ($row) {
            return $row;
        }

        return static::query()->create(static::defaults());
    }

    public function logoUrl(): ?string
    {
        if ($this->logo_path && Storage::disk('public')->exists($this->logo_path)) {
            return Storage::disk('public')->url($this->logo_path);
        }

        try {
            return BrandingSetting::current()->logoUrl();
        } catch (\Throwable) {
            $fallback = public_path('assets/img/logo2.png');
            if (is_file($fallback)) {
                return asset('assets/img/logo2.png');
            }
        }

        return null;
    }

    public function logoAbsolutePath(): ?string
    {
        if ($this->logo_path && Storage::disk('public')->exists($this->logo_path)) {
            return Storage::disk('public')->path($this->logo_path);
        }

        try {
            return BrandingSetting::current()->logoAbsolutePath();
        } catch (\Throwable) {
            $fallback = public_path('assets/img/logo2.png');
            if (is_file($fallback)) {
                return $fallback;
            }
        }

        return null;
    }

    public function signatureUrl(): ?string
    {
        if ($this->signature_path && Storage::disk('public')->exists($this->signature_path)) {
            return Storage::disk('public')->url($this->signature_path);
        }

        return null;
    }

    public function addressLines(): array
    {
        $raw = trim((string) ($this->company_address ?: ''));
        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw) ?: [])));
    }
}
