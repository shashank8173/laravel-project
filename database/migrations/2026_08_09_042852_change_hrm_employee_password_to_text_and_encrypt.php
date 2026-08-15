<?php

use App\Casts\EncryptedPassword;
use App\Models\Employee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Encrypted payloads can exceed varchar(255).
        // Relax sql_mode so ALTER does not fail on legacy zero-dates in other columns.
        $modes = DB::select('SELECT @@SESSION.sql_mode AS m');
        $previousMode = $modes[0]->m ?? '';
        DB::statement("SET SESSION sql_mode=''");
        try {
            DB::statement('ALTER TABLE hrm_employee MODIFY password TEXT NULL');
        } finally {
            DB::statement('SET SESSION sql_mode=?', [$previousMode]);
        }

        Employee::query()
            ->whereNotNull('password')
            ->where('password', '!=', '')
            ->orderBy('id')
            ->chunkById(100, function ($employees) {
                foreach ($employees as $employee) {
                    $raw = (string) $employee->getRawOriginal('password');
                    if ($raw === '') {
                        continue;
                    }
                    if (EncryptedPassword::isEncryptedPayload($raw) || EncryptedPassword::isHashedPassword($raw)) {
                        continue;
                    }

                    DB::table('hrm_employee')
                        ->where('id', $employee->id)
                        ->update(['password' => Crypt::encryptString($raw)]);
                }
            });

        // Encrypt SMTP password if still plaintext.
        $smtp = DB::table('hrm_email_configurations')->where('config_key', 'SMTP_PASSWORD')->first();
        if ($smtp && is_string($smtp->config_value) && $smtp->config_value !== '') {
            $raw = (string) $smtp->config_value;
            if (! EncryptedPassword::isEncryptedPayload($raw)) {
                DB::table('hrm_email_configurations')
                    ->where('config_key', 'SMTP_PASSWORD')
                    ->update(['config_value' => Crypt::encryptString($raw)]);
            }
        }
    }

    public function down(): void
    {
        $modes = DB::select('SELECT @@SESSION.sql_mode AS m');
        $previousMode = $modes[0]->m ?? '';
        DB::statement("SET SESSION sql_mode=''");
        try {
            if (Schema::hasColumn('hrm_employee', 'password')) {
                DB::statement('ALTER TABLE hrm_employee MODIFY password VARCHAR(255) NULL');
            }
        } finally {
            DB::statement('SET SESSION sql_mode=?', [$previousMode]);
        }
    }
};
