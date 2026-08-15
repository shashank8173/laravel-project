<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Notification;
use App\Services\EmployeeNotificationService;
use App\Services\HrmMailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ActivityController extends Controller
{
    /**
     * Legacy activities.php — announcements list + add/edit/delete.
     */
    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));

        $notifications = Notification::query()
            ->with('sender')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('title', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $employees = Employee::query()
            ->with(['department:id,name', 'designation:id,name'])
            ->where('status', 1)
            ->where('archive_status', 0)
            ->where('id', '!=', 14)
            ->orderBy('fname')
            ->orderBy('lname')
            ->get(['id', 'fname', 'lname', 'office_email', 'department_id', 'designation_id', 'emp_id']);

        $isAdmin = Auth::user()?->isAdmin() ?? false;
        $totalAll = Notification::query()->count();

        return view('activities.index', compact('notifications', 'employees', 'isAdmin', 'q', 'totalAll'));
    }

    public function store(Request $request, HrmMailer $mailer, EmployeeNotificationService $notifier): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'emp_send_to' => ['required', 'array', 'min:1'],
            'emp_send_to.*' => ['integer', 'exists:hrm_employee,id'],
        ]);

        $employeeIds = array_values(array_unique(array_map('intval', $data['emp_send_to'])));
        $employeeIds = array_values(array_filter($employeeIds, fn (int $id) => $id > 0 && $id !== 14));

        if ($employeeIds === []) {
            return back()
                ->withInput()
                ->with('error', 'Please select at least one employee to send the announcement.');
        }

        $sendTo = implode(',', $employeeIds);
        $plainBody = Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($data['description'])) ?: ''), 500);

        Notification::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'sent_by' => Auth::id(),
            'send_to' => $sendTo,
            'date' => now()->format('d-m-Y'),
            'time' => now()->format('h:i:s'),
        ]);

        // In-app bell / inbox for every selected employee
        $notifier->notifyMany(
            $employeeIds,
            'announcement',
            $data['title'],
            $plainBody !== '' ? $plainBody : null,
            route('notifications.inbox'),
            Auth::user()
        );

        $mailed = $this->sendAnnouncementMails($mailer, $employeeIds, $data['title'], $data['description']);

        $msg = 'Announcement sent to '.count($employeeIds).' employee(s)';
        if ($mailed > 0) {
            $msg .= ' · email queued to '.$mailed;
        } else {
            $msg .= ' · no office email found for mail (in-app notification still delivered)';
        }

        return back()->with('success', $msg);
    }

    public function update(Request $request, Notification $notification): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ]);

        $notification->update([
            'title' => $data['title'],
            'description' => $data['description'],
        ]);

        return back()->with('success', 'Announcement updated successfully!');
    }

    public function destroy(Notification $notification): RedirectResponse
    {
        $this->authorizeAdmin();
        $notification->delete();

        return back()->with('success', 'Announcement deleted successfully!');
    }

    private function authorizeAdmin(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403);
        }
    }

    /**
     * @param  array<int, int>  $employeeIds
     */
    private function sendAnnouncementMails(HrmMailer $mailer, array $employeeIds, string $title, string $description): int
    {
        $emails = Employee::officialEmailsFor($employeeIds);
        if ($emails === []) {
            return 0;
        }

        $html = '<div style="font-family:Arial,sans-serif;line-height:1.5;color:#172033">'
            .'<h2 style="margin:0 0 12px;font-size:18px;">'.e($title).'</h2>'
            .'<div>'.$description.'</div>'
            .'</div>';

        $sent = 0;
        foreach ($emails as $email) {
            if ($mailer->send($email, 'HR Announcement: '.$title, $html)) {
                $sent++;
            }
        }

        return $sent;
    }
}
