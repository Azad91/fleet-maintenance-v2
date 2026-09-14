<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\CompanyStoreRequest;
use App\Http\Requests\SuperAdmin\CompanyUpdateRequest;
use App\Models\Company;
use App\Models\User;
use App\Services\Onboarding\CompanyOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CompanyController extends Controller
{

    public function __construct(
        protected CompanyOnboardingService $onboardingService
    ) {}

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
    public function store(CompanyStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (empty($validated['slug'])) {
            $validated['slug'] = $this->generateUniqueSlug($validated['name']);
        }

        $company = $this->onboardingService->createWithDirector(
            companyData: [
                'name'      => $validated['name'],
                'slug'      => $validated['slug'],
                'email'     => $validated['email'] ?? null,
                'phone'     => $validated['phone'] ?? null,
                'address'   => $validated['address'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ],
            directorData: [
                'name'     => $validated['director_name'],
                'email'    => $validated['director_email'],
                'password' => $validated['director_password'],
                'pin'      => $validated['director_pin'],
            ],
        );

        return redirect()
            ->route('super-admin.companies.show', $company)
            ->with('success', __('messages.super_admin.companies.created_with_director', [
                'name'     => $company->name,
                'director' => $validated['director_email'],
            ]));
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
    public function update(CompanyUpdateRequest $request, Company $company): RedirectResponse
    {
        $validated = $request->validated();
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
     * Generate a unique slug from the given name.
     */
    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Company::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
