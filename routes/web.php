<?php

use App\Http\Controllers\AdminLogController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\ArchivedEmployeeController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyDataController;
use App\Http\Controllers\ConnectorController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailSettingsController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeLookupController;
use App\Http\Controllers\EmployeeOfTheMonthController;
use App\Http\Controllers\EmployeeNotificationController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\BrandingSettingsController;
use App\Http\Controllers\DeveloperCronController;
use App\Http\Controllers\GreetingSettingsController;
use App\Http\Controllers\HarassmentController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LeaveSettingController;
use App\Http\Controllers\MigrationStatusController;
use App\Http\Controllers\NoticePeriodController;
use App\Http\Controllers\NoticePeriodStepController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\OptionalSetupController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\PasswordGenerateController;
use App\Http\Controllers\PoshController;
use App\Http\Controllers\PolicyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportingManagerController;
use App\Http\Controllers\ResignationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\NotificationSettingsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'employee'])->name('dashboard.employee');
    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])->name('dashboard.admin');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo');
    Route::post('/theme', [ThemeController::class, 'update'])->name('theme.update');
    Route::get('/change-password', [ProfileController::class, 'editPassword'])->name('password.edit');
    Route::put('/change-password', [ProfileController::class, 'updatePassword'])->name('password.update');

    // Searchable employee picker (name / department / designation)
    Route::get('/lookups/employees', [EmployeeLookupController::class, 'search'])->name('lookups.employees');
    Route::get('/lookups/employee-filters', [EmployeeLookupController::class, 'filters'])->name('lookups.employee-filters');

    Route::get('/leaves', [LeaveController::class, 'employeeIndex'])->name('leaves.employee');
    Route::post('/leaves', [LeaveController::class, 'store'])->name('leaves.store');

    Route::get('/holidays', [HolidayController::class, 'index'])->name('holidays.index');

    // Project Management (auth; authorization via ProjectPolicy)
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::post('/projects/{project}/assign', [ProjectController::class, 'assign'])->name('projects.assign');
    Route::post('/projects/{project}/reassign', [ProjectController::class, 'reassign'])->name('projects.reassign');
    Route::delete('/projects/{project}/members/{employee}', [ProjectController::class, 'removeMember'])->name('projects.members.remove');
    Route::post('/projects/{project}/status', [ProjectController::class, 'updateStatus'])->name('projects.status');
    Route::post('/projects/{project}/progress', [ProjectController::class, 'updateProgress'])->name('projects.progress');
    Route::post('/projects/{project}/daily-notes', [ProjectController::class, 'storeDailyNote'])->name('projects.notes.store');
    Route::delete('/projects/{project}/daily-notes/{note}', [ProjectController::class, 'destroyDailyNote'])->name('projects.notes.destroy');
    Route::post('/projects/{project}/tasks', [ProjectController::class, 'storeTask'])->name('projects.tasks.store');
    Route::post('/projects/{project}/tasks/{task}/status', [ProjectController::class, 'updateTaskStatus'])->name('projects.tasks.status');
    Route::post('/projects/{project}/tasks/{task}/notes', [ProjectController::class, 'storeTaskNote'])->name('projects.tasks.notes.store');
    Route::delete('/projects/{project}/tasks/{task}', [ProjectController::class, 'destroyTask'])->name('projects.tasks.destroy');

    Route::get('/attendance', [AttendanceController::class, 'myAttendance'])->name('attendance.mine');
    Route::post('/attendance/punch-in', [AttendanceController::class, 'punchIn'])->name('attendance.punch-in');
    Route::post('/attendance/punch-out', [AttendanceController::class, 'punchOut'])->name('attendance.punch-out');
    // Legacy attandance-all-employee.php (self / team / admin scoped in controller)
    Route::get('/attendance/all-employees', [AttendanceController::class, 'allEmployees'])->name('attendance.all');

    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/manage', [TicketController::class, 'manage'])->name('tickets.manage');
    Route::post('/tickets/categories', [TicketController::class, 'storeCategory'])->name('tickets.categories.store');
    Route::put('/tickets/categories/{category}', [TicketController::class, 'updateCategory'])->name('tickets.categories.update');
    Route::delete('/tickets/categories/{category}', [TicketController::class, 'destroyCategory'])->name('tickets.categories.destroy');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.status');
    Route::post('/tickets/{ticket}/comments', [TicketController::class, 'comment'])->name('tickets.comment');

    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/contacts', [ChatController::class, 'contacts'])->name('chat.contacts');
    Route::get('/chat/messages', [ChatController::class, 'messages'])->name('chat.messages');
    Route::post('/chat/send', [ChatController::class, 'send'])->name('chat.send');
    Route::get('/chat/unread-count', [ChatController::class, 'unreadCount'])->name('chat.unread');
    Route::post('/chat/heartbeat', [ChatController::class, 'heartbeat'])->name('chat.heartbeat');
    Route::get('/chat/group-members', [ChatController::class, 'groupMembers'])->name('chat.group-members');
    Route::post('/chat/groups', [ChatController::class, 'createGroup'])->name('chat.groups.create');
    Route::post('/chat/groups/add-members', [ChatController::class, 'addGroupMembers'])->name('chat.groups.add-members');
    Route::post('/chat/groups/remove-member', [ChatController::class, 'removeGroupMember'])->name('chat.groups.remove-member');
    Route::post('/chat/groups/exit', [ChatController::class, 'exitGroup'])->name('chat.groups.exit');
    Route::post('/chat/groups/delete', [ChatController::class, 'deleteGroup'])->name('chat.groups.delete');
    Route::post('/chat/groups/edit', [ChatController::class, 'editGroup'])->name('chat.groups.edit');
    Route::post('/chat/clear', [ChatController::class, 'clearChat'])->name('chat.clear');
    Route::post('/chat/messages/delete', [ChatController::class, 'deleteMessage'])->name('chat.messages.delete');
    Route::post('/chat/messages/edit', [ChatController::class, 'editMessage'])->name('chat.messages.edit');

    Route::get('/chat/call/config', [CallController::class, 'config'])->name('chat.call.config');
    Route::get('/chat/call/poll', [CallController::class, 'poll'])->name('chat.call.poll');
    Route::post('/chat/call/start', [CallController::class, 'start'])->name('chat.call.start');
    Route::post('/chat/call/accept', [CallController::class, 'accept'])->name('chat.call.accept');
    Route::post('/chat/call/reject', [CallController::class, 'reject'])->name('chat.call.reject');
    Route::post('/chat/call/end', [CallController::class, 'end'])->name('chat.call.end');
    Route::post('/chat/call/invite', [CallController::class, 'invite'])->name('chat.call.invite');
    Route::post('/chat/call/signal', [CallController::class, 'signal'])->name('chat.call.signal');

    Route::get('/expenses', [ExpenseController::class, 'my'])->name('expenses.mine');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');

    Route::get('/resignation', [ResignationController::class, 'my'])->name('resignation.mine');
    Route::post('/resignation', [ResignationController::class, 'store'])->name('resignation.store');
    Route::delete('/resignation/{resignation}', [ResignationController::class, 'destroy'])->name('resignation.destroy');

    Route::get('/harassment', [HarassmentController::class, 'create'])->name('harassment.create');
    Route::post('/harassment', [HarassmentController::class, 'store'])->name('harassment.store');

    Route::get('/policies', [PolicyController::class, 'index'])->name('policies.index');
    Route::get('/policies/{companyPolicy}/download', [PolicyController::class, 'download'])->name('policies.download');
    Route::get('/company-data', [CompanyDataController::class, 'index'])->name('company-data.index');
    Route::get('/company-data/{companyData}/download', [CompanyDataController::class, 'download'])->name('company-data.download');
    Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');

    // Legacy activities.php (announcements)
    Route::get('/activities', [ActivityController::class, 'index'])->name('activities.index');
    Route::get('/notifications', [NotificationSettingsController::class, 'index'])->name('settings.notifications');
    Route::put('/notifications', [NotificationSettingsController::class, 'update'])->name('settings.notifications.update');
    Route::get('/my-notifications', [EmployeeNotificationController::class, 'index'])->name('notifications.inbox');
    Route::get('/my-notifications/poll', [EmployeeNotificationController::class, 'poll'])->name('notifications.poll');
    Route::post('/my-notifications/read-all', [EmployeeNotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/my-notifications/{notification}/read', [EmployeeNotificationController::class, 'markRead'])->name('notifications.read');

    Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
    Route::get('/posh/guidelines', [PoshController::class, 'guidelines'])->name('posh.guidelines');
    Route::get('/posh/committee', [PoshController::class, 'committee'])->name('posh.committee');
    Route::get('/employee-of-the-month', [EmployeeOfTheMonthController::class, 'index'])->name('eom.index');

    Route::get('/migration-status', MigrationStatusController::class)->name('migration.status');

    // Reporting managers + admins (legacy leaves.php / attendance for managers)
    Route::middleware('manager')->group(function () {
        Route::get('/admin/leaves', [LeaveController::class, 'adminIndex'])->name('leaves.admin');
        Route::patch('/admin/leaves/{leave}', [LeaveController::class, 'updateStatus'])->name('leaves.status');
        Route::get('/admin/attendance', [AttendanceController::class, 'adminReport'])->name('attendance.admin');
    });

    Route::middleware('admin')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::post('/employees/{employee}/photo', [EmployeeController::class, 'updatePhoto'])->name('employees.photo');
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
        Route::post('/employees/{employee}/family', [EmployeeController::class, 'storeFamily'])->name('employees.family.store');
        Route::delete('/employees/{employee}/family/{family}', [EmployeeController::class, 'destroyFamily'])->name('employees.family.destroy');
        Route::post('/employees/{employee}/education', [EmployeeController::class, 'storeEducation'])->name('employees.education.store');
        Route::delete('/employees/{employee}/education/{education}', [EmployeeController::class, 'destroyEducation'])->name('employees.education.destroy');

        Route::get('/admin/attendance/upload', [AttendanceController::class, 'uploadForm'])->name('attendance.upload');
        Route::post('/admin/attendance/upload', [AttendanceController::class, 'upload'])->name('attendance.upload.store');
        Route::post('/attendance/update-record', [AttendanceController::class, 'updateRecord'])->name('attendance.update-record');
        Route::post('/attendance/delete-record', [AttendanceController::class, 'destroyRecord'])->name('attendance.delete-record');

        Route::post('/activities', [ActivityController::class, 'store'])->name('activities.store');
        Route::put('/activities/{notification}', [ActivityController::class, 'update'])->name('activities.update');
        Route::delete('/activities/{notification}', [ActivityController::class, 'destroy'])->name('activities.destroy');

        Route::get('/departments', [OrganizationController::class, 'departments'])->name('departments.index');
        Route::post('/departments', [OrganizationController::class, 'storeDepartment'])->name('departments.store');
        Route::put('/departments/{department}', [OrganizationController::class, 'updateDepartment'])->name('departments.update');
        Route::delete('/departments/{department}', [OrganizationController::class, 'destroyDepartment'])->name('departments.destroy');

        Route::get('/designations', [OrganizationController::class, 'designations'])->name('designations.index');
        Route::post('/designations', [OrganizationController::class, 'storeDesignation'])->name('designations.store');
        Route::put('/designations/{designation}', [OrganizationController::class, 'updateDesignation'])->name('designations.update');
        Route::delete('/designations/{designation}', [OrganizationController::class, 'destroyDesignation'])->name('designations.destroy');

        // Use /hrm-assets — /assets collides with public/assets static folder (Apache 403)
        Route::get('/hrm-assets', [AssetController::class, 'index'])->name('assets.index');
        Route::get('/hrm-assets/{asset}', [AssetController::class, 'show'])->name('assets.show');
        Route::post('/hrm-assets', [AssetController::class, 'store'])->name('assets.store');
        Route::put('/hrm-assets/{asset}', [AssetController::class, 'update'])->name('assets.update');
        Route::delete('/hrm-assets/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy');
        Route::get('/asset-assignments', [AssetController::class, 'assignments'])->name('assets.assignments');
        Route::post('/asset-assignments', [AssetController::class, 'assign'])->name('assets.assign');
        Route::post('/asset-assignments/{assignment}/return', [AssetController::class, 'returnAsset'])->name('assets.return');
        Route::delete('/asset-assignments/{assignment}', [AssetController::class, 'destroyAssignment'])->name('assets.assignment.destroy');

        Route::get('/salary', [SalaryController::class, 'index'])->name('salary.index');
        Route::get('/salary/calculate', [SalaryController::class, 'calculate'])->name('salary.calculate');
        Route::post('/salary/payslip-settings', [SalaryController::class, 'updatePayslipSettings'])->name('salary.payslip-settings');
        Route::post('/salary/send-slip', [SalaryController::class, 'sendSlip'])->name('salary.send-slip');
        Route::put('/salary/{employee}', [SalaryController::class, 'update'])->name('salary.update');
        Route::get('/advance-salary', [SalaryController::class, 'advances'])->name('salary.advances');
        Route::post('/advance-salary', [SalaryController::class, 'storeAdvance'])->name('salary.advances.store');

        Route::get('/admin/expenses', [ExpenseController::class, 'adminIndex'])->name('expenses.admin');
        Route::get('/admin/expenses/pdf', [ExpenseController::class, 'adminPdf'])->name('expenses.admin.pdf');
        Route::post('/admin/expenses/share', [ExpenseController::class, 'adminShare'])->name('expenses.admin.share');
        Route::post('/admin/expenses', [ExpenseController::class, 'adminStore'])->name('expenses.admin.store');
        Route::put('/admin/expenses/{expense}', [ExpenseController::class, 'adminUpdate'])->name('expenses.admin.update');
        Route::delete('/admin/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.admin.destroy');
        Route::patch('/admin/expenses/{expense}', [ExpenseController::class, 'updateStatus'])->name('expenses.status');
        Route::post('/expense-categories', [ExpenseController::class, 'storeCategory'])->name('expenses.categories.store');
        Route::put('/expense-categories/{category}', [ExpenseController::class, 'updateCategory'])->name('expenses.categories.update');
        Route::delete('/expense-categories/{category}', [ExpenseController::class, 'destroyCategory'])->name('expenses.categories.destroy');
        Route::post('/expense-companies', [ExpenseController::class, 'storeExpenseCompany'])->name('expenses.companies.store');
        Route::put('/expense-companies/{expenseCompany}', [ExpenseController::class, 'updateExpenseCompany'])->name('expenses.companies.update');
        Route::delete('/expense-companies/{expenseCompany}', [ExpenseController::class, 'destroyExpenseCompany'])->name('expenses.companies.destroy');

        Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
        Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
        Route::post('/policies', [PolicyController::class, 'store'])->name('policies.store');
        Route::put('/policies/{companyPolicy}', [PolicyController::class, 'update'])->name('policies.update');
        Route::delete('/policies/{companyPolicy}', [PolicyController::class, 'destroy'])->name('policies.destroy');

        Route::post('/company-data', [CompanyDataController::class, 'store'])->name('company-data.store');
        Route::put('/company-data/{companyData}', [CompanyDataController::class, 'update'])->name('company-data.update');
        Route::delete('/company-data/{companyData}', [CompanyDataController::class, 'destroy'])->name('company-data.destroy');

        Route::get('/admin/resignations', [ResignationController::class, 'adminIndex'])->name('resignation.admin');
        Route::patch('/admin/resignations/{resignation}', [ResignationController::class, 'updateStatus'])->name('resignation.status');

        Route::get('/notice-period', [NoticePeriodController::class, 'index'])->name('notice-period.index');
        Route::get('/notice-period/history', [NoticePeriodController::class, 'history'])->name('notice-period.history');
        Route::get('/notice-period/files/{file}/download', [NoticePeriodController::class, 'downloadFile'])->name('notice-period.files.download');
        Route::delete('/notice-period/files/{file}', [NoticePeriodController::class, 'destroyFile'])->name('notice-period.files.destroy');
        Route::post('/notice-period/employee-steps/{employeeStep}', [NoticePeriodController::class, 'updateStep'])->name('notice-period.step.update');
        Route::get('/notice-period/{employee}', [NoticePeriodController::class, 'show'])->name('notice-period.show');
        Route::post('/notice-period/{employee}/resignation', [NoticePeriodController::class, 'updateResignation'])->name('notice-period.resignation');

        Route::get('/notice-period-steps', [NoticePeriodStepController::class, 'index'])->name('notice-period-steps.index');
        Route::post('/notice-period-steps/reorder', [NoticePeriodStepController::class, 'reorder'])->name('notice-period-steps.reorder');
        Route::post('/notice-period-steps', [NoticePeriodStepController::class, 'store'])->name('notice-period-steps.store');
        Route::put('/notice-period-steps/{noticePeriodStep}', [NoticePeriodStepController::class, 'update'])->name('notice-period-steps.update');
        Route::delete('/notice-period-steps/{noticePeriodStep}', [NoticePeriodStepController::class, 'destroy'])->name('notice-period-steps.destroy');

        Route::post('/posh/settings', [PoshController::class, 'updateSettings'])->name('posh.settings');
        Route::post('/posh/sections', [PoshController::class, 'storeSection'])->name('posh.sections.store');
        Route::put('/posh/sections/{section}', [PoshController::class, 'updateSection'])->name('posh.sections.update');
        Route::delete('/posh/sections/{section}', [PoshController::class, 'destroySection'])->name('posh.sections.destroy');
        Route::post('/posh/members', [PoshController::class, 'storeMember'])->name('posh.members.store');
        Route::put('/posh/members/{member}', [PoshController::class, 'updateMember'])->name('posh.members.update');
        Route::delete('/posh/members/{member}', [PoshController::class, 'destroyMember'])->name('posh.members.destroy');

        Route::get('/admin/harassment', [HarassmentController::class, 'adminIndex'])->name('harassment.admin');

        Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding.index');
        Route::post('/onboarding/master-steps', [OnboardingController::class, 'storeMasterStep'])->name('onboarding.master');
        Route::post('/onboarding/master-steps/reorder', [OnboardingController::class, 'reorderMasterSteps'])->name('onboarding.master.reorder');
        Route::put('/onboarding/master-steps/{masterStep}', [OnboardingController::class, 'updateMasterStep'])->name('onboarding.master.update');
        Route::delete('/onboarding/master-steps/{masterStep}', [OnboardingController::class, 'destroyMasterStep'])->name('onboarding.master.destroy');
        Route::post('/onboarding/steps/{step}', [OnboardingController::class, 'updateStep'])->name('onboarding.step.update');
        Route::delete('/onboarding/files/{file}', [OnboardingController::class, 'destroyFile'])->name('onboarding.file.destroy');

        Route::get('/office-timing', [SettingsController::class, 'officeTiming'])->name('settings.office-timing');
        Route::post('/office-timing', [SettingsController::class, 'saveOfficeTiming'])->name('settings.office-timing.save');
        Route::get('/greeting-cards', [GreetingSettingsController::class, 'index'])->name('settings.greetings');
        Route::put('/greeting-cards', [GreetingSettingsController::class, 'update'])->name('settings.greetings.update');
        Route::post('/greeting-cards/images', [GreetingSettingsController::class, 'storeImage'])->name('settings.greetings.images.store');
        Route::patch('/greeting-cards/images/{greetingImage}', [GreetingSettingsController::class, 'toggleImage'])->name('settings.greetings.images.toggle');
        Route::delete('/greeting-cards/images/{greetingImage}', [GreetingSettingsController::class, 'destroyImage'])->name('settings.greetings.images.destroy');
        Route::post('/holidays', [SettingsController::class, 'storeHoliday'])->name('holidays.store');
        Route::delete('/holidays/{holiday}', [SettingsController::class, 'destroyHoliday'])->name('holidays.destroy');

        Route::post('/employee-of-the-month', [EmployeeOfTheMonthController::class, 'store'])->name('eom.store');
        Route::put('/employee-of-the-month/{eom}', [EmployeeOfTheMonthController::class, 'update'])->name('eom.update');
        Route::delete('/employee-of-the-month/{eom}', [EmployeeOfTheMonthController::class, 'destroy'])->name('eom.destroy');

        Route::get('/archived-employees', [ArchivedEmployeeController::class, 'index'])->name('employees.archived');
        Route::get('/archived-employees/{archived}/details', [ArchivedEmployeeController::class, 'details'])->name('employees.archived.details');
        Route::get('/archived-employees/{archived}/payslip', [ArchivedEmployeeController::class, 'payslip'])->name('employees.archived.payslip');
        Route::put('/archived-employees/{archived}/salary', [ArchivedEmployeeController::class, 'updateSalary'])->name('employees.archived.salary');
        Route::post('/archived-employees/{archived}/restore', [ArchivedEmployeeController::class, 'restore'])->name('employees.archived.restore');

        Route::get('/leave-settings', [LeaveSettingController::class, 'index'])->name('leave-settings.index');
        Route::post('/leave-settings', [LeaveSettingController::class, 'store'])->name('leave-settings.store');
        Route::put('/leave-settings/{leaveType}', [LeaveSettingController::class, 'update'])->name('leave-settings.update');
        Route::delete('/leave-settings/{leaveType}', [LeaveSettingController::class, 'destroy'])->name('leave-settings.destroy');

        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/overtime', [OvertimeController::class, 'index'])->name('overtime.index');

        Route::get('/reporting-managers', [ReportingManagerController::class, 'index'])->name('reporting.index');
        Route::post('/reporting-managers', [ReportingManagerController::class, 'store'])->name('reporting.store');
        Route::delete('/reporting-managers/{reportingManager}', [ReportingManagerController::class, 'destroy'])->name('reporting.destroy');

        Route::get('/candidates', [CandidateController::class, 'index'])->name('candidates.index');
        Route::post('/candidates', [CandidateController::class, 'store'])->name('candidates.store');
        Route::put('/candidates/{candidate}', [CandidateController::class, 'update'])->name('candidates.update');
        Route::post('/candidates/{candidate}/hire', [CandidateController::class, 'hire'])->name('candidates.hire');
        Route::delete('/candidates/{candidate}', [CandidateController::class, 'destroy'])->name('candidates.destroy');
        Route::get('/candidates/{candidate}/resume', [CandidateController::class, 'downloadResume'])->name('candidates.resume');
    });

    Route::middleware('superadmin')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::put('/roles/{employee}', [RoleController::class, 'update'])->name('roles.update');
        Route::get('/email-settings', [EmailSettingsController::class, 'index'])->name('settings.email');
        Route::put('/email-settings', [EmailSettingsController::class, 'update'])->name('settings.email.update');
        Route::post('/email-settings/test', [EmailSettingsController::class, 'test'])->name('settings.email.test');
        Route::get('/developer/optional-setup', [OptionalSetupController::class, 'index'])->name('developer.optional-setup');
        Route::post('/developer/optional-setup', [OptionalSetupController::class, 'update'])->name('developer.optional-setup.update');
        Route::get('/optional-setup', fn () => redirect()->route('developer.optional-setup'));
        Route::get('/developer/api-tokens', [ApiTokenController::class, 'index'])->name('developer.api-tokens');
        Route::post('/developer/api-tokens', [ApiTokenController::class, 'store'])->name('developer.api-tokens.store');
        Route::post('/developer/api-tokens/{apiToken}/revoke', [ApiTokenController::class, 'revoke'])->name('developer.api-tokens.revoke');
        Route::delete('/developer/api-tokens/{apiToken}', [ApiTokenController::class, 'destroy'])->name('developer.api-tokens.destroy');
        Route::get('/developer/connectors', [ConnectorController::class, 'index'])->name('developer.connectors');
        Route::post('/developer/connectors', [ConnectorController::class, 'store'])->name('developer.connectors.store');
        Route::put('/developer/connectors/{connector}', [ConnectorController::class, 'update'])->name('developer.connectors.update');
        Route::delete('/developer/connectors/{connector}', [ConnectorController::class, 'destroy'])->name('developer.connectors.destroy');
        Route::post('/developer/connectors/{connector}/pull', [ConnectorController::class, 'pull'])->name('developer.connectors.pull');
        Route::post('/developer/connectors/{connector}/test', [ConnectorController::class, 'testSample'])->name('developer.connectors.test');
        Route::post('/developer/connectors/{connector}/reset-map', [ConnectorController::class, 'resetMap'])->name('developer.connectors.reset-map');
        Route::get('/developer/integration-guide', fn () => view('developer.integration-guide'))->name('developer.integration-guide');
        Route::get('/developer/connector-training', fn () => view('developer.connector-training'))->name('developer.connector-training');
        Route::get('/password-generate', [PasswordGenerateController::class, 'index'])->name('developer.passwords');
        Route::post('/password-generate/bulk-reset', [PasswordGenerateController::class, 'resetBulk'])->name('developer.passwords.bulk');
        Route::post('/password-generate/{employee}/reset', [PasswordGenerateController::class, 'resetOne'])->name('developer.passwords.reset');
        Route::get('/branding', [BrandingSettingsController::class, 'index'])->name('developer.branding');
        Route::put('/branding', [BrandingSettingsController::class, 'update'])->name('developer.branding.update');
        Route::get('/cron-jobs', [DeveloperCronController::class, 'index'])->name('developer.cron');
        Route::get('/cron-jobs/test-celebrations', [DeveloperCronController::class, 'previewCelebrations'])->name('developer.cron.celebrations.preview');
        Route::get('/cron-jobs/test-celebrations/send', [DeveloperCronController::class, 'sendCelebrations'])->name('developer.cron.celebrations.send');
        Route::match(['get', 'post'], '/cron-jobs/test-celebrations/force', [DeveloperCronController::class, 'forceCelebrations'])->name('developer.cron.celebrations.force');
        Route::get('/cron-jobs/test-holidays', [DeveloperCronController::class, 'previewHolidays'])->name('developer.cron.holidays.preview');
        Route::get('/cron-jobs/test-holidays/send', [DeveloperCronController::class, 'sendHolidays'])->name('developer.cron.holidays.send');
        Route::match(['get', 'post'], '/cron-jobs/test-holidays/force', [DeveloperCronController::class, 'forceHoliday'])->name('developer.cron.holidays.force');
        Route::get('/admin-logs', [AdminLogController::class, 'index'])->name('logs.admin');
    });
});