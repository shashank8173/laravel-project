<?php

namespace App\Http\Controllers;

use App\Models\CompanyData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CompanyDataController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $user = $request->user();
        $isAdmin = (bool) ($user?->isAdmin());

        $documents = CompanyData::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('document_name', 'like', "%{$q}%")
                        ->orWhere('document_type', 'like', "%{$q}%")
                        ->orWhere('file_path', 'like', "%{$q}%");
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $types = CompanyData::query()
            ->whereNotNull('document_type')
            ->where('document_type', '!=', '')
            ->distinct()
            ->orderBy('document_type')
            ->pluck('document_type');

        return view('company-data.index', compact('documents', 'q', 'isAdmin', 'types'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'document_name' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'string', 'max:100'],
            'updated_on' => ['nullable', 'date'],
            'file' => ['required', 'file', 'max:12288'],
        ]);

        $path = $request->file('file')->store('company-data', 'public');

        CompanyData::create([
            'document_name' => $data['document_name'],
            'document_type' => $data['document_type'],
            'updated_on' => $data['updated_on'] ?? now()->toDateString(),
            'file_path' => $path,
        ]);

        return back()->with('success', 'Document added.');
    }

    public function update(Request $request, CompanyData $companyData): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'document_name' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'string', 'max:100'],
            'updated_on' => ['nullable', 'date'],
            'file' => ['nullable', 'file', 'max:12288'],
        ]);

        $companyData->document_name = $data['document_name'];
        $companyData->document_type = $data['document_type'];
        $companyData->updated_on = $data['updated_on'] ?? now()->toDateString();

        if ($request->hasFile('file')) {
            $this->deleteStoredFile($companyData->file_path);
            $companyData->file_path = $request->file('file')->store('company-data', 'public');
        }

        $companyData->save();

        return back()->with('success', 'Document updated.');
    }

    public function destroy(Request $request, CompanyData $companyData): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $this->deleteStoredFile($companyData->file_path);
        $companyData->delete();

        return back()->with('success', 'Document deleted.');
    }

    public function download(CompanyData $companyData): BinaryFileResponse
    {
        $absolute = $this->resolveAbsolutePath($companyData->file_path);

        if (! $absolute || ! is_file($absolute)) {
            abort(404, 'Document file not found.');
        }

        $downloadName = basename($companyData->file_path) ?: ('document-'.$companyData->id);

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
