<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyController extends Controller
{
    /**
     * List all companies with their garages count and users count.
     */
    public function index(): View
    {
        $this->ensureSuperAdmin();

        $companies = Company::withCount(['garages'])
            ->orderBy('name')
            ->paginate(20);

        return view('super-admin.companies.index', compact('companies'));
    }

    /**
     * Show the form for creating a new company.
     */
    public function create(): View
    {
        $this->ensureSuperAdmin();

        return view('super-admin.companies.create');
    }

    /**
     * Store a newly created company in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'slug'      => ['nullable', 'string', 'max:255', 'unique:companies,slug', 'regex:/^[a-z0-9-]+$/'],
            'email'     => ['nullable', 'email', 'max:255'],
            'phone'     => ['nullable', 'string', 'max:50'],
            'address'   => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // Auto-generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = $this->generateUniqueSlug($validated['name']);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $company = Company::create($validated);

        return redirect()
            ->route('super-admin.companies.show', $company)
            ->with('success', __('messages.super_admin.companies.created'));
    }

    /**
     * Display the specified company with garages and directors.
     */
    public function show(Company $company): View
    {
        $this->ensureSuperAdmin();

        $company->load([
            'garages' => fn ($q) => $q->orderBy('name'),
            'directors',
        ]);

        // Users not already directors of this company
        $directorIds = $company->directors->pluck('id')->toArray();

        $availableUsers = User::where('role', 'user')
            ->whereNotIn('id', $directorIds)
            ->orderBy('name')
            ->get();

        return view('super-admin.companies.show', compact('company', 'availableUsers'));
    }

    /**
     * Show the form for editing the specified company.
     */
    public function edit(Company $company): View
    {
        $this->ensureSuperAdmin();

        return view('super-admin.companies.edit', compact('company'));
    }

    /**
     * Update the specified company in storage.
     */
    public function update(Request $request, Company $company): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'slug'      => [
                'required', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('companies', 'slug')->ignore($company->id),
            ],
            'email'     => ['nullable', 'email', 'max:255'],
            'phone'     => ['nullable', 'string', 'max:50'],
            'address'   => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $company->update($validated);

        return redirect()
            ->route('super-admin.companies.index')
            ->with('success', __('messages.super_admin.companies.updated'));
    }

    /**
     * Soft delete the specified company.
     *
     * Only allowed if there are no active garages under this company.
     */
    public function destroy(Company $company): RedirectResponse
    {
        $this->ensureSuperAdmin();

        // Block deletion if company has active garages
        $activeGarages = $company->garages()->where('is_active', true)->count();

        if ($activeGarages > 0) {
            return back()->with('error', __('messages.super_admin.companies.cannot_delete_active_garages', [
                'count' => $activeGarages,
            ]));
        }

        $company->delete();

        return redirect()
            ->route('super-admin.companies.index')
            ->with('success', __('messages.super_admin.companies.deleted'));
    }

    /**
     * Ensure the authenticated user is a super admin.
     */
    private function ensureSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    /**
     * Generate a unique slug from the given name.
     */
    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Company::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
