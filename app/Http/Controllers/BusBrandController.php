<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusBrandStoreRequest;
use App\Http\Requests\BusBrandUpdateRequest;
use App\Models\BusBrand;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * CRUD for the per-garage bus brand catalog.
 *
 * Access is restricted to Garage Admins via the route middleware
 * (`role:admin`) and reinforced by BusBrandPolicy. Super Admins pass
 * every check through the Gate::before() bypass.
 */
class BusBrandController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', BusBrand::class);

        $brands = BusBrand::withCount('buses')
            ->orderBy('name')
            ->paginate(config('settings.pagination', 25));

        return view('bus-brands.index', compact('brands'));
    }

    public function create(): View
    {
        $this->authorize('create', BusBrand::class);

        return view('bus-brands.create');
    }

    public function store(BusBrandStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', BusBrand::class);

        BusBrand::create($request->validated());

        return redirect()
            ->route('bus-brands.index')
            ->with('success', __('messages.flash.created', ['Item' => 'Brand']));
    }

    public function edit(BusBrand $busBrand): View
    {
        $this->authorize('update', $busBrand);

        return view('bus-brands.edit', compact('busBrand'));
    }

    public function update(BusBrandUpdateRequest $request, BusBrand $busBrand): RedirectResponse
    {
        $this->authorize('update', $busBrand);

        $busBrand->update($request->validated());

        return redirect()
            ->route('bus-brands.index')
            ->with('success', __('messages.flash.updated', ['Item' => 'Brand']));
    }

    public function destroy(BusBrand $busBrand): RedirectResponse
    {
        $this->authorize('delete', $busBrand);

        // Guard: prevent deletion while dependent rows exist.
        // The DB FK is RESTRICT, so the delete would fail anyway —
        // but we check first to return a clear, translated message.
        if ($busBrand->buses()->exists()) {
            return back()->with('error', __('messages.bus_brands.cannot_delete_has_buses'));
        }

        if ($busBrand->motorOilDetails()->exists()) {
            return back()->with('error', __('messages.bus_brands.cannot_delete_has_motor_oil'));
        }

        $busBrand->delete();

        return redirect()
            ->route('bus-brands.index')
            ->with('success', __('messages.flash.deleted', ['Item' => 'Brand']));
    }
}
