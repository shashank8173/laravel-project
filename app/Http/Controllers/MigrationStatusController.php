<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class MigrationStatusController extends Controller
{
    public function __invoke(): View
    {
        $modules = [
            ['name' => 'Auth (Login/Logout + admin logs)', 'status' => 'done', 'legacy' => 'index.php, loginck.php, logout.php, admin-logs.php'],
            ['name' => 'Admin / Employee Dashboard', 'status' => 'done', 'legacy' => 'admin-dashboard.php, employee-dashboard.php'],
            ['name' => 'Profile + Change Password', 'status' => 'done', 'legacy' => 'profile.php, change-password.php'],
            ['name' => 'Employees CRUD + bank/family/education', 'status' => 'done', 'legacy' => 'employees.php, edit-employees.php'],
            ['name' => 'Archived employees + restore', 'status' => 'done', 'legacy' => 'archived_employees.php'],
            ['name' => 'Assign Roles', 'status' => 'done', 'legacy' => 'assignrole.php'],
            ['name' => 'Leaves + Leave Settings', 'status' => 'done', 'legacy' => 'leaves.php, leave-settings.php'],
            ['name' => 'Holidays CRUD', 'status' => 'done', 'legacy' => 'holidays.php'],
            ['name' => 'Attendance + Excel/CSV upload', 'status' => 'done', 'legacy' => 'attendance-*.php, upload-attendance-admin.php'],
            ['name' => 'Overtime (from punch data)', 'status' => 'done', 'legacy' => 'overtime.php'],
            ['name' => 'Departments + Designations', 'status' => 'done', 'legacy' => 'departments.php, designations.php'],
            ['name' => 'Reporting Managers', 'status' => 'done', 'legacy' => 'update-reporting-manager-ajax.php'],
            ['name' => 'Tickets + comments', 'status' => 'done', 'legacy' => 'ticket.php, view-ticket.php'],
            ['name' => 'Chat + file attachments', 'status' => 'done', 'legacy' => 'chat_page.php'],
            ['name' => 'Payroll / Salary / Advances', 'status' => 'done', 'legacy' => 'calculate-salary.php, salary-*.php'],
            ['name' => 'Assets + Assignments', 'status' => 'done', 'legacy' => 'hrm_assets.php'],
            ['name' => 'Onboarding + Resignation', 'status' => 'done', 'legacy' => 'onboarding.php, apply_resignation.php'],
            ['name' => 'POSH / Harassment + ICC', 'status' => 'done', 'legacy' => 'harresment.php, guidelineposh.php'],
            ['name' => 'Expenses + Companies + Policies', 'status' => 'done', 'legacy' => 'manage_expenses.php, companies_*.php'],
            ['name' => 'Office Timing + Notifications + Email/Cron', 'status' => 'done', 'legacy' => 'office-timing.php, email-settings.php'],
            ['name' => 'Analytics', 'status' => 'done', 'legacy' => 'analytics.php'],
            ['name' => 'Employee of the Month', 'status' => 'done', 'legacy' => 'hrm_employee_of_the_month.php'],
            ['name' => 'Candidates', 'status' => 'done', 'legacy' => 'candidates.php (new hrm_candidates table)'],
            ['name' => 'Contacts directory', 'status' => 'done', 'legacy' => 'contact-list.php'],
        ];

        return view('migration.status', compact('modules'));
    }
}
