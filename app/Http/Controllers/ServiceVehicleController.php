<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceVehicleStoreRequest;
use App\Http\Requests\ServiceVehicleUpdateRequest;
use App\Models\ServiceVehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\ServiceVehicleStock;
use Illuminate\Http\Request;

class ServiceVehicleController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', ServiceVehicle::class);

        $vehicles = ServiceVehicle::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(config('settings.pagination', 25));

        return view('service-vehicles.index', compact('vehicles'));
    }

    /**
     * Landing page for the "service vehicle stocks" section.
     *
     * Shows the list of vehicles with a summary of what each one holds.
     * Clicking a card opens the vehicle's `show` page, which renders the
     * full item list for that specific vehicle — the warehouse-style flow
     * the operators are used to.
     */
    public function stocks(): View
    {
        $this->authorize('viewAny', ServiceVehicle::class);

        $vehicles = ServiceVehicle::withCount('stocks')
            ->withSum('stocks', 'quantity')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('service-vehicles.stocks', compact('vehicles'));
    }

    public function create(): View
    {
        $this->authorize('create', ServiceVehicle::class);

        return view('service-vehicles.create');
    }

    public function store(ServiceVehicleStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', ServiceVehicle::class);

        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        ServiceVehicle::create($validated);

        return redirect()->route('service-vehicles.index')
            ->with('success', __('messages.flash.created', ['Item' => 'Service vehicle']));
    }

    public function show(int $id): View
    {
        $vehicle = ServiceVehicle::with('stocks')->findOrFail($id);

        $this->authorize('view', $vehicle);

        return view('service-vehicles.show', compact('vehicle'));
    }

    public function edit(int $id): View
    {
        $vehicle = ServiceVehicle::findOrFail($id);

        $this->authorize('update', $vehicle);

        return view('service-vehicles.edit', compact('vehicle'));
    }

    public function update(ServiceVehicleUpdateRequest $request, int $id): RedirectResponse
    {
        $vehicle = ServiceVehicle::findOrFail($id);

        $this->authorize('update', $vehicle);

        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        $vehicle->update($validated);

        return redirect()->route('service-vehicles.index')
            ->with('success', __('messages.flash.updated', ['Item' => 'Service vehicle']));
    }

    public function destroy(int $id): RedirectResponse
    {
        $vehicle = ServiceVehicle::findOrFail($id);

        $this->authorize('delete', $vehicle);

        $vehicle->delete();

        return redirect()->route('service-vehicles.index')
            ->with('success', __('messages.flash.deleted', ['Item' => 'Service vehicle']));
    }
}
