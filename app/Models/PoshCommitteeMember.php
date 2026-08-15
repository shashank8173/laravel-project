<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoshCommitteeMember extends Model
{
    protected $table = 'posh_committee_members';

    protected $fillable = [
        'role_title',
        'name',
        'phone',
        'email',
        'notes',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public static function seedDefaultsIfEmpty(): void
    {
        if (static::query()->exists()) {
            return;
        }

        $members = [
            ['Presiding Officer', 'Kanu Paul', '+91 96502 00202', 'shashanksinghc8173@gmail.com', null],
            ['Member', 'Ritika Rajan', '+91 88260 10745', 'samratsingh8173@gmail.com', null],
            ['Member', 'Prem Rai', '+91 88515 54402', 'prem@2solutions.biz', null],
            ['External Member', 'Dr. Nidhi Singh', null, null, 'Faculty, MDI'],
        ];

        foreach ($members as $i => [$role, $name, $phone, $email, $notes]) {
            static::create([
                'role_title' => $role,
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'notes' => $notes,
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $out = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $out .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $out !== '' ? $out : 'M';
    }
}
