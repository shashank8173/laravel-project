<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateController extends Controller
{
    public const STATUSES = ['applied', 'interview', 'selected', 'hired', 'rejected', 'on-hold'];

    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $status = $request->get('status') ?: null;

        if ($status && ! in_array($status, self::STATUSES, true)) {
            $status = null;
        }

        $candidates = Candidate::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('mobile', 'like', "%{$q}%")
                        ->orWhere('position', 'like', "%{$q}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $counts = [
            'all' => Candidate::query()->count(),
            'applied' => Candidate::query()->where('status', 'applied')->count(),
            'interview' => Candidate::query()->where('status', 'interview')->count(),
            'selected' => Candidate::query()->where('status', 'selected')->count(),
            'hired' => Candidate::query()->where('status', 'hired')->count(),
            'rejected' => Candidate::query()->where('status', 'rejected')->count(),
            'on-hold' => Candidate::query()->where('status', 'on-hold')->count(),
        ];

        $statuses = self::STATUSES;

        return view('candidates.index', compact('candidates', 'q', 'status', 'counts', 'statuses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data = array_merge($data, $this->storeResume($request));

        Candidate::create($data);

        return back()->with('success', 'Candidate added.');
    }

    public function update(Request $request, Candidate $candidate): RedirectResponse
    {
        $data = $this->validatedData($request);

        if ($request->hasFile('resume')) {
            $candidate->deleteResumeFile();
            $data = array_merge($data, $this->storeResume($request));
        }

        if ($request->boolean('remove_resume') && ! $request->hasFile('resume')) {
            $candidate->deleteResumeFile();
            $data['resume_path'] = null;
            $data['resume_name'] = null;
        }

        $candidate->update($data);

        return back()->with('success', 'Candidate updated.');
    }

    public function destroy(Candidate $candidate): RedirectResponse
    {
        $candidate->deleteResumeFile();
        $candidate->delete();

        return back()->with('success', 'Candidate removed.');
    }

    public function hire(Candidate $candidate): RedirectResponse
    {
        if (! in_array($candidate->status, ['selected', 'interview'], true)) {
            return back()->withErrors(['candidate' => 'Only selected (or interviewed) candidates can be hired.']);
        }

        if ($candidate->status === 'hired') {
            return back()->withErrors(['candidate' => 'Candidate is already hired.']);
        }

        $email = trim((string) $candidate->email);
        if ($email !== '' && Employee::query()
            ->where(function ($q) use ($email) {
                $q->where('office_email', $email)->orWhere('email', $email);
            })
            ->exists()) {
            return back()->withErrors(['candidate' => 'An employee with this email already exists.']);
        }

        $parts = preg_split('/\s+/', trim((string) $candidate->name), 2) ?: [];
        $fname = $parts[0] ?? 'New';
        $lname = $parts[1] ?? '';

        $employee = DB::transaction(function () use ($candidate, $fname, $lname, $email) {
            $employee = Employee::create([
                'fname' => mb_substr($fname, 0, 208),
                'lname' => mb_substr($lname, 0, 58),
                'email' => $email !== '' ? $email : null,
                'office_email' => $email !== '' ? $email : null,
                'mobile1' => $candidate->mobile,
                'job_title' => $candidate->position,
                'password' => config('hrm.default_employee_password', 'Welcome@123'),
                'role' => 'user',
                'status' => 1,
                'archive_status' => 0,
                'doj' => now()->toDateString(),
                'added_date' => now(),
            ]);

            $note = trim((string) $candidate->notes);
            $hireNote = 'Hired as employee #'.$employee->id.' on '.now()->toDateString();
            $candidate->update([
                'status' => 'hired',
                'notes' => $note === '' ? $hireNote : $note."\n".$hireNote,
            ]);

            return $employee;
        });

        return redirect()
            ->route('employees.edit', $employee)
            ->with('success', 'Candidate hired. Complete employee profile details below.');
    }

    public function downloadResume(Candidate $candidate): StreamedResponse
    {
        if (! $candidate->hasResume() || ! Storage::disk('public')->exists($candidate->resume_path)) {
            abort(404, 'Resume not found.');
        }

        $name = $candidate->resume_name ?: basename($candidate->resume_path);

        return Storage::disk('public')->download($candidate->resume_path, $name);
    }

    /** @return array{name: string, email: ?string, mobile: ?string, position: ?string, status: string, notes: ?string} */
    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'position' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:'.implode(',', self::STATUSES)],
            'notes' => ['nullable', 'string'],
            'resume' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'remove_resume' => ['nullable', 'boolean'],
        ]);

        unset($data['resume'], $data['remove_resume']);

        return $data;
    }

    /** @return array{resume_path?: string, resume_name?: string} */
    private function storeResume(Request $request): array
    {
        if (! $request->hasFile('resume')) {
            return [];
        }

        $file = $request->file('resume');

        return [
            'resume_path' => $file->store('resumes', 'public'),
            'resume_name' => $file->getClientOriginalName(),
        ];
    }
}
