<?php

namespace App\Http\Controllers;

use App\Models\PoshCommitteeMember;
use App\Models\PoshGuidelineSection;
use App\Models\PoshSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PoshController extends Controller
{
    public function guidelines(): View
    {
        PoshGuidelineSection::seedDefaultsIfEmpty();
        $settings = PoshSetting::current();
        $sections = PoshGuidelineSection::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $isAdmin = (bool) (Auth::user()?->isAdmin());

        return view('posh.guidelines', compact('settings', 'sections', 'isAdmin'));
    }

    public function committee(): View
    {
        PoshCommitteeMember::seedDefaultsIfEmpty();
        $settings = PoshSetting::current();
        $members = PoshCommitteeMember::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $isAdmin = (bool) (Auth::user()?->isAdmin());

        return view('posh.committee', compact('settings', 'members', 'isAdmin'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'guidelines_title' => ['nullable', 'string', 'max:255'],
            'guidelines_intro' => ['nullable', 'string'],
            'committee_title' => ['nullable', 'string', 'max:255'],
            'committee_intro' => ['nullable', 'string'],
            'committee_footer' => ['nullable', 'string'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ]);

        $settings = PoshSetting::current();
        $payload = [];
        foreach ([
            'guidelines_title',
            'guidelines_intro',
            'committee_title',
            'committee_intro',
            'committee_footer',
            'contact_email',
        ] as $key) {
            if ($request->has($key)) {
                $payload[$key] = $data[$key] ?? null;
            }
        }
        if ($payload !== []) {
            $settings->fill($payload)->save();
        }

        return back()->with('success', 'POSH settings saved.');
    }

    public function storeSection(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'heading' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $max = (int) PoshGuidelineSection::query()->max('sort_order');

        PoshGuidelineSection::create([
            'heading' => $data['heading'],
            'body' => $data['body'],
            'sort_order' => $max + 1,
            'is_active' => true,
        ]);

        return back()->with('success', 'Guideline section added.');
    }

    public function updateSection(Request $request, PoshGuidelineSection $section): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'heading' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $section->update([
            'heading' => $data['heading'],
            'body' => $data['body'],
            'sort_order' => $data['sort_order'] ?? $section->sort_order,
            'is_active' => $request->boolean('is_active', $section->is_active),
        ]);

        return back()->with('success', 'Guideline section updated.');
    }

    public function destroySection(PoshGuidelineSection $section): RedirectResponse
    {
        $this->authorizeAdmin();
        $section->delete();

        return back()->with('success', 'Guideline section deleted.');
    }

    public function storeMember(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'role_title' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $max = (int) PoshCommitteeMember::query()->max('sort_order');

        PoshCommitteeMember::create([
            ...$data,
            'sort_order' => $max + 1,
            'is_active' => true,
        ]);

        return back()->with('success', 'Committee member added.');
    }

    public function updateMember(Request $request, PoshCommitteeMember $member): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'role_title' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $member->update([
            'role_title' => $data['role_title'],
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'notes' => $data['notes'] ?? null,
            'sort_order' => $data['sort_order'] ?? $member->sort_order,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $member->is_active,
        ]);

        return back()->with('success', 'Committee member updated.');
    }

    public function destroyMember(PoshCommitteeMember $member): RedirectResponse
    {
        $this->authorizeAdmin();
        $member->delete();

        return back()->with('success', 'Committee member removed.');
    }

    private function authorizeAdmin(): void
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403);
        }
    }
}
