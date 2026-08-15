<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $company = Company::query()->orderBy('id')->first();
        $isAdmin = (bool) ($request->user()?->isAdmin());
        $editing = $isAdmin && ($request->boolean('edit') || ($company === null && $request->boolean('add')));

        return view('companies.index', compact('company', 'isAdmin', 'editing'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (Company::query()->exists()) {
            return redirect()
                ->route('companies.index', ['edit' => 1])
                ->withErrors(['name' => 'Company details already exist. Please edit the existing company.']);
        }

        $data = $this->validated($request);
        $data = $this->applyUploads($request, $data);

        Company::create($data);

        return redirect()->route('companies.index')->with('success', 'Company details added.');
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $data = $this->validated($request);
        $data = $this->applyUploads($request, $data, $company);

        $company->update($data);

        return redirect()->route('companies.index')->with('success', 'Company details updated.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'mobile1' => ['nullable', 'string', 'max:30'],
            'mobile2' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:100'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'linkedin' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'twitter' => ['nullable', 'string', 'max:255'],
            'founded_year' => ['nullable', 'integer', 'min:1800', 'max:2100'],
            'employee_count' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'description' => ['nullable', 'string'],
            'operating_hours' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'string', 'max:100'],
            'longitude' => ['nullable', 'string'],
            'parent_company' => ['nullable', 'string', 'max:255'],
            'additional_contact' => ['nullable', 'string', 'max:255'],
            'address1' => ['nullable', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'logo_alt_text' => ['nullable', 'string', 'max:255'],
            'banner_alt_text' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:6144'],
        ]);
    }

    private function applyUploads(Request $request, array $data, ?Company $company = null): array
    {
        unset($data['logo'], $data['banner']);

        if ($request->hasFile('logo')) {
            if ($company?->logo) {
                $this->deleteStoredImage($company->logo);
            }
            $data['logo'] = $request->file('logo')->store('company', 'public');
        }

        if ($request->hasFile('banner')) {
            if ($company?->banner) {
                $this->deleteStoredImage($company->banner);
            }
            $data['banner'] = $request->file('banner')->store('company', 'public');
        }

        return $data;
    }

    private function deleteStoredImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
