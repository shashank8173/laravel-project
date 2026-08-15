<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoshSetting extends Model
{
    protected $table = 'posh_settings';

    protected $fillable = [
        'guidelines_title',
        'guidelines_intro',
        'committee_title',
        'committee_intro',
        'committee_footer',
        'contact_email',
    ];

    public static function defaults(): array
    {
        return [
            'guidelines_title' => 'POSH Policy - Expetize Private Limited (1Solutions.biz)',
            'guidelines_intro' => 'At Expetize Private Limited, operating under the brand 1Solutions.biz, we are committed to providing a safe, secure, and respectful work environment free from sexual harassment.',
            'committee_title' => 'Internal Complaints Committee (ICC)',
            'committee_intro' => 'The company has constituted an Internal Complaints Committee (ICC) in accordance with the Sexual Harassment of Women at Workplace Act. The current members are:',
            'committee_footer' => 'The ICC is responsible for receiving, investigating, and redressing complaints related to sexual harassment in a fair, confidential, and timely manner.',
            'contact_email' => 'shashanksinghc8173@gmail.com',
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
}
