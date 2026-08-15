<?php

namespace App\Http\Controllers;

use App\Models\EmailConfiguration;
use App\Models\Employee;
use App\Models\HarassmentComplaint;
use App\Services\HrmMailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HarassmentController extends Controller
{
    public function create(): View
    {
        $user = Auth::user()->load(['department', 'designation']);
        $employees = Employee::query()
            ->with(['department:id,name', 'designation:id,name'])
            ->where('status', 1)
            ->where('archive_status', 0)
            ->where('id', '!=', $user->id)
            ->orderBy('fname')
            ->get(['id', 'fname', 'lname', 'department_id', 'designation_id', 'emp_id']);

        $isAdmin = (bool) ($user->isAdmin());
        $myCount = HarassmentComplaint::query()->where('emp_id', $user->id)->count();

        return view('harassment.create', compact('user', 'employees', 'isAdmin', 'myCount'));
    }

    public function store(Request $request, HrmMailer $mailer): RedirectResponse
    {
        $user = Auth::user()->load(['department', 'designation']);

        $data = $request->validate([
            'complainant_contact' => ['required', 'string', 'max:255'],
            'incident_date' => ['required', 'date'],
            'incident_location' => ['required', 'string', 'max:255'],
            'alleged_harasser_id' => ['nullable', 'integer', 'exists:hrm_employee,id'],
            'harasser_details' => ['required', 'string'],
            'incident_description' => ['required', 'string'],
            'witness_details' => ['nullable', 'string'],
            'evidence' => ['nullable', 'file', 'max:5120'],
            'declaration' => ['accepted'],
        ]);

        $path = null;
        if ($request->hasFile('evidence')) {
            $path = $request->file('evidence')->store('harassment', 'public');
        }

        $complaint = HarassmentComplaint::create([
            'emp_id' => $user->id,
            'complainant_name' => $user->full_name,
            'complainant_contact' => $data['complainant_contact'],
            'complainant_department' => $user->department?->name ?? '',
            'complainant_designation' => $user->designation?->name ?? '',
            'incident_date' => $data['incident_date'],
            'incident_location' => $data['incident_location'],
            'alleged_harasser_id' => $data['alleged_harasser_id'] ?? null,
            'harasser_details' => $data['harasser_details'],
            'incident_description' => $data['incident_description'],
            'witness_details' => $data['witness_details'] ?? null,
            'evidence_path' => $path,
            'submission_date' => now(),
        ]);

        $recipients = array_values(array_filter(
            EmailConfiguration::recipients('HARASSMENT_RECIPIENTS'),
            fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL)
        ));

        if ($recipients !== []) {
            $subject = 'Confidential POSH complaint #'.$complaint->id;
            $body = 'A new harassment complaint has been submitted.<br><br>'
                .'<strong>Complaint ID:</strong> '.$complaint->id.'<br>'
                .'<strong>Complainant:</strong> '.e($complaint->complainant_name).'<br>'
                .'<strong>Department:</strong> '.e((string) $complaint->complainant_department).'<br>'
                .'<strong>Incident date:</strong> '.e((string) $complaint->incident_date).'<br>'
                .'<strong>Location:</strong> '.e((string) $complaint->incident_location).'<br><br>'
                .'Please review this in the admin harassment queue. This email does not include sensitive narrative details.';

            $to = array_shift($recipients);
            $mailer->send($to, $subject, $body, $recipients);
        }

        return redirect()->route('harassment.create')->with('success', 'Complaint submitted confidentially.');
    }

    public function adminIndex(): View
    {
        $complaints = HarassmentComplaint::query()
            ->with('employee')
            ->latest('id')
            ->paginate(30);

        return view('harassment.admin', compact('complaints'));
    }
}
