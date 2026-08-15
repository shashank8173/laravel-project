<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrm_connectors', function (Blueprint $table) {
            if (! Schema::hasColumn('hrm_connectors', 'slug')) {
                $table->string('slug', 80)->nullable()->after('type');
            }
        });

        // Must drop type unique before multiple rows can be type=generic.
        if ($this->indexExists('hrm_connectors', 'hrm_connectors_type_unique')) {
            Schema::table('hrm_connectors', function (Blueprint $table) {
                $table->dropUnique('hrm_connectors_type_unique');
            });
        }

        $rows = DB::table('hrm_connectors')->orderBy('id')->get();
        foreach ($rows as $row) {
            $base = Str::slug((string) ($row->slug ?: $row->name ?: 'connector')) ?: 'connector';
            $slug = $row->slug ?: ($base.'-'.$row->id);

            DB::table('hrm_connectors')->where('id', $row->id)->update([
                'type' => 'generic',
                'slug' => $slug,
                'name' => 'Connector '.$row->id,
            ]);
        }

        if (DB::table('hrm_connectors')->count() === 0) {
            DB::table('hrm_connectors')->insert([
                'type' => 'generic',
                'slug' => 'main',
                'name' => 'Main Connector',
                'enabled' => 0,
                'webhook_secret' => 'whsec_'.Str::random(40),
                'field_map' => json_encode([
                    'first_name' => 'fname',
                    'last_name' => 'lname',
                    'email' => 'office_email',
                    'employee_id' => 'external_id',
                    'employee_code' => 'emp_id',
                    'phone' => 'mobile1',
                    'department' => 'department',
                    'designation' => 'designation',
                    'job_title' => 'job_title',
                    'date_of_joining' => 'doj',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! $this->indexExists('hrm_connectors', 'hrm_connectors_slug_unique')) {
            Schema::table('hrm_connectors', function (Blueprint $table) {
                $table->unique('slug');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('hrm_connectors', 'hrm_connectors_slug_unique')) {
            Schema::table('hrm_connectors', function (Blueprint $table) {
                $table->dropUnique('hrm_connectors_slug_unique');
            });
        }

        if (Schema::hasColumn('hrm_connectors', 'slug')) {
            Schema::table('hrm_connectors', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return collect(DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]))->isNotEmpty();
    }
};
