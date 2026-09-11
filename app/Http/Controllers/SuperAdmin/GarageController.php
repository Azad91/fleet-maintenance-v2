<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Garage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GarageController extends Controller
{
    /**
     * List all garages with optional filter by company.
     */
    public function index(Request $request): View
    {
        $this->ensureSuperAdmin();

        $query = Garage::with('company')
            ->withCount('users');

        // Filter by company
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        // Filter by active status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $garages = $query->orderBy('name')->paginate(20);

        $companies = Company::orderBy('name')->get();

        return view('super-admin.garages.index', compact('garages', 'companies'));
    }

    /**
     * Show the form for creating a new garage.
     */
    public function create(Request $request): View
    {
        $this->ensureSuperAdmin();

        $companies = Company::where('is_active', true)->orderBy('name')->get();

        // Pre-select company if provided in query string
        $selectedCompanyId = $request->integer('company_id');

        return view('super-admin.garages.create', compact('companies', 'selectedCompanyId'));
    }

    /**
     * Store a newly created garage in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'name'       => ['required', 'string', 'max:255'],
            'code'       => ['required', 'string', 'max:50', 'unique:garages,code'],
            'address'    => ['nullable', 'string', 'max:1000'],
            'phone'      => ['nullable', 'string', 'max:50'],
            'is_active'  => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $garage = Garage::create($validated);

        return redirect()
            ->route('super-admin.garages.show', $garage)
            ->with('success', __('messages.super_admin.garages.created'));
    }

    /**
     * Display the specified garage with its admins and users.
     */
    public function show(Garage $garage): View
    {
        $this->ensureSuperAdmin();

        $garage->load([
            'company',
            'users' => fn ($q) => $q->orderBy('name'),
        ]);

        return view('super-admin.garages.show', compact('garage'));
    }

    /**
     * Show the form for editing the specified garage.
     */
    public function edit(Garage $garage): View
    {
        $this->ensureSuperAdmin();

        $companies = Company::orderBy('name')->get();

        return view('super-admin.garages.edit', compact('garage', 'companies'));
    }

    /**
     * Update the specified garage in storage.
     */
    public function update(Request $request, Garage $garage): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'name'       => ['required', 'string', 'max:255'],
            'code'       => [
                'required', 'string', 'max:50',
                Rule::unique('garages', 'code')->ignore($garage->id),
            ],
            'address'    => ['nullable', 'string', 'max:1000'],
            'phone'      => ['nullable', 'string', 'max:50'],
            'is_active'  => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $garage->update($validated);

        return redirect()
            ->route('super-admin.garages.index')
            ->with('success', __('messages.super_admin.garages.updated'));
    }

    /**
     * Soft delete the specified garage.
     *
     * Only allowed if there are no active users or buses in the garage.
     */
    public function destroy(Garage $garage): RedirectResponse
    {
        $this->ensureSuperAdmin();

        // Block deletion if garage has active users
        $activeUsers = $garage->users()->wherePivot('is_active', true)->count();

        if ($activeUsers > 0) {
            return back()->with('error', __('messages.super_admin.garages.cannot_delete_active_users', [
                'count' => $activeUsers,
            ]));
        }

        $garage->delete();

        return redirect()
            ->route('super-admin.garages.index')
            ->with('success', __('messages.super_admin.garages.deleted'));
    }

    /**
     * Ensure the authenticated user is a super admin.
     */
    private function ensureSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }
}
