<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DummyEmployeesSeeder extends Seeder
{
    public function run(): void
    {
        $deptIds = DB::table('hrm_department')->pluck('id')->all();
        $desigIds = DB::table('hrm_designation')->pluck('id')->all();

        if ($deptIds === [] || $desigIds === []) {
            throw new \RuntimeException('Departments/designations missing. Seed those first.');
        }

        $dependent = [
            'employee_notice_period_steps',
            'employee_onboarding_steps',
            'employee_resignations',
            'notice_period_files',
            'onboarding_files',
            'hrm_advance_salary',
            'hrm_asset_assignments',
            'hrm_deduction_history',
            'hrm_salary_management',
            'hrm_chat_group_members',
            'hrm_chat_messages',
            'hrm_chat_groups',
            'ticket_comments',
            'tickets',
            'hrm_employee_family',
            'hrm_employee_education',
            'hrm_bank_detail',
            'hrm_leave_applied',
            'newuser_attendance',
            'employee_expenses',
            'hrm_reporting_manager',
            'hrm_notification',
            'hrm_employee_of_the_month',
            'admin_login_logs',
            'archived_employees',
            'resignation_history',
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($dependent as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        DB::table('hrm_employee')->delete();
        // Reset auto-increment for clean IDs
        DB::statement('ALTER TABLE hrm_employee AUTO_INCREMENT = 1');

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $people = [
            ['Atul', 'Sharma', 'super admin', 'Super Admin', 'admin@test.com'],
            ['Priya', 'Verma', 'admin', 'HR Manager', 'hr.admin@test.com'],
            ['Rahul', 'Singh', 'user', 'Software Developer', 'rahul.singh@test.com'],
            ['Neha', 'Gupta', 'user', 'UI Designer', 'neha.gupta@test.com'],
            ['Amit', 'Patel', 'user', 'Backend Developer', 'amit.patel@test.com'],
            ['Sneha', 'Reddy', 'user', 'QA Engineer', 'sneha.reddy@test.com'],
            ['Vikram', 'Mehta', 'user', 'Project Manager', 'vikram.mehta@test.com'],
            ['Ananya', 'Iyer', 'user', 'Business Analyst', 'ananya.iyer@test.com'],
            ['Karan', 'Malhotra', 'user', 'DevOps Engineer', 'karan.malhotra@test.com'],
            ['Pooja', 'Nair', 'user', 'Content Writer', 'pooja.nair@test.com'],
            ['Rohan', 'Joshi', 'user', 'Support Executive', 'rohan.joshi@test.com'],
            ['Ishita', 'Kapoor', 'user', 'Recruiter', 'ishita.kapoor@test.com'],
            ['Saurabh', 'Yadav', 'user', 'Accountant', 'saurabh.yadav@test.com'],
            ['Meera', 'Das', 'user', 'Marketing Executive', 'meera.das@test.com'],
            ['Arjun', 'Bansal', 'user', 'Full Stack Developer', 'arjun.bansal@test.com'],
            ['Kavya', 'Pillai', 'user', 'Data Analyst', 'kavya.pillai@test.com'],
            ['Nikhil', 'Chauhan', 'user', 'Network Admin', 'nikhil.chauhan@test.com'],
            ['Divya', 'Saxena', 'user', 'Graphic Designer', 'divya.saxena@test.com'],
            ['Manish', 'Tiwari', 'user', 'Sales Executive', 'manish.tiwari@test.com'],
            ['Ritu', 'Agarwal', 'user', 'Office Coordinator', 'ritu.agarwal@test.com'],
        ];

        $now = now();
        $password = 'Welcome@123';

        foreach ($people as $i => [$fname, $lname, $role, $job, $email]) {
            $n = $i + 1;
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '.', trim($fname.' '.$lname)));
            $slug = trim($slug, '.');
            $officeEmail = match ($role) {
                'super admin' => 'superadmin@leadforgrow.com',
                'admin' => 'hr@leadforgrow.com',
                default => $slug.'@leadforgrow.com',
            };

            Employee::query()->create([
                'fname' => $fname,
                'lname' => $lname,
                'email' => $email,
                'office_email' => $officeEmail,
                'mobile1' => '98'.str_pad((string) (10000000 + $n * 137), 8, '0', STR_PAD_LEFT),
                'password' => $password,
                'role' => $role,
                'status' => 1,
                'archive_status' => 0,
                'ui_theme' => 'light',
                'department_id' => $deptIds[$i % count($deptIds)],
                'designation_id' => $desigIds[$i % count($desigIds)],
                'job_title' => $job,
                'emp_id' => 'EMP-'.str_pad((string) $n, 3, '0', STR_PAD_LEFT),
                'attendance_id' => $n,
                'salary' => (string) (25000 + ($n * 1500)),
                'employee_type' => 'Full Time',
                'work_location' => 'Registered Office',
                'gender' => ($n % 2 === 0) ? 2 : 1,
                'marital_status' => ($n % 3 === 0) ? 1 : 2,
                'dob' => $now->copy()->subYears(22 + ($n % 12))->subDays($n * 11)->startOfDay(),
                'doj' => $now->copy()->subYears(1 + ($n % 4))->subMonths($n % 10)->startOfDay(),
                'experience' => (($n % 5) + 1).' Year',
                'current_address' => $n.' Dummy Street, Sector '.($n + 10).', New Delhi',
                'permanent_address' => $n.' Dummy Street, Sector '.($n + 10).', New Delhi',
                'city_id' => 'New Delhi',
                'state_id' => 'Delhi',
                'pincode' => '1100'.str_pad((string) ($n % 99), 2, '0', STR_PAD_LEFT),
                'religion' => 'Hindu',
                'nationality' => 'Indian',
                'bgroup' => ['A positive', 'B positive', 'O positive', 'AB positive'][$i % 4],
                'fathers_name' => 'Father of '.$fname,
                'added_date' => $now,
                'update_date' => $now,
            ]);
        }
    }
}
