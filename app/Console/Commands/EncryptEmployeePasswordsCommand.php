<?php

namespace App\Console\Commands;

use App\Casts\EncryptedPassword;
use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EncryptEmployeePasswordsCommand extends Command
{
    protected $signature = 'hrm:encrypt-passwords {--force : Re-scan and encrypt any remaining plaintext}';

    protected $description = 'Encrypt plain-text employee passwords in hrm_employee (reversible for admin view)';

    public function handle(): int
    {
        $updated = 0;
        $skipped = 0;

        Employee::query()
            ->whereNotNull('password')
            ->where('password', '!=', '')
            ->orderBy('id')
            ->chunkById(100, function ($employees) use (&$updated, &$skipped) {
                foreach ($employees as $employee) {
                    $raw = (string) $employee->getRawOriginal('password');
                    if ($raw === '') {
                        $skipped++;
                        continue;
                    }
                    if (EncryptedPassword::isEncryptedPayload($raw) || EncryptedPassword::isHashedPassword($raw)) {
                        $skipped++;
                        continue;
                    }

                    DB::table('hrm_employee')
                        ->where('id', $employee->id)
                        ->update(['password' => Crypt::encryptString($raw)]);
                    $updated++;
                }
            });

        $this->info("Encrypted {$updated} password(s). Skipped {$skipped} (already encrypted/hashed/empty).");

        return self::SUCCESS;
    }
}
