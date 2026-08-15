<?php

namespace App\Http\Controllers;

use App\Models\CompanyPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PolicyController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $user = $request->user();
        $isAdmin = (bool) ($user?->isAdmin());

        $policies = CompanyPolicy::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('policy_name', 'like', "%{$q}%")
                        ->orWhere('policy_type', 'like', "%{$q}%")
                        ->orWhere('file_path', 'like', "%{$q}%");
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $types = CompanyPolicy::query()
            ->whereNotNull('policy_type')
            ->where('policy_type', '!=', '')
            ->distinct()
            ->orderBy('policy_type')
            ->pluck('policy_type');

        return view('policies.index', compact('policies', 'q', 'isAdmin', 'types'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'policy_name' => ['required', 'string', 'max:255'],
            'policy_type' => ['required', 'string', 'max:100'],
            'updated_on' => ['nullable', 'date'],
            'file' => ['required', 'file', 'max:12288'],
        ]);

        $path = $request->file('file')->store('policies', 'public');

        CompanyPolicy::create([
            'policy_name' => $data['policy_name'],
            'policy_type' => $data['policy_type'],
            'updated_on' => $data['updated_on'] ?? now()->toDateString(),
            'file_path' => $path,
        ]);

        return back()->with('success', 'Policy created.');
    }

    public function update(Request $request, CompanyPolicy $companyPolicy): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'policy_name' => ['required', 'string', 'max:255'],
            'policy_type' => ['required', 'string', 'max:100'],
            'updated_on' => ['nullable', 'date'],
            'file' => ['nullable', 'file', 'max:12288'],
        ]);

        $companyPolicy->policy_name = $data['policy_name'];
        $companyPolicy->policy_type = $data['policy_type'];
        $companyPolicy->updated_on = $data['updated_on'] ?? now()->toDateString();

        if ($request->hasFile('file')) {
            $this->deleteStoredFile($companyPolicy->file_path);
            $companyPolicy->file_path = $request->file('file')->store('policies', 'public');
        }

        $companyPolicy->save();

        return back()->with('success', 'Policy updated.');
    }

    public function destroy(Request $request, CompanyPolicy $companyPolicy): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $this->deleteStoredFile($companyPolicy->file_path);
        $companyPolicy->delete();

        return back()->with('success', 'Policy deleted.');
    }

    public function download(CompanyPolicy $companyPolicy): StreamedResponse|\Illuminate\Http\Response
    {
        $absolute = $this->resolveAbsolutePath($companyPolicy->file_path);

        if (! $absolute || ! is_file($absolute)) {
            abort(404, 'Policy file not found.');
        }

        $downloadName = basename($companyPolicy->file_path) ?: ('policy-'.$companyPolicy->id);

        return response()->download($absolute, $downloadName);
    }

    private function authorizeAdmin(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! $user->isAdmin()) {
            abort(403);
        }
    }

    private function deleteStoredFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function resolveAbsolutePath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->path($path);
        }

        $candidates = [
            public_path($path),
            public_path('storage/'.$path),
            base_path('../hrmpulse_live-main/'.$path),
            base_path('../hrmpulse_live-main/public/'.$path),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
