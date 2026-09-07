<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusStoreRequest;
use App\Http\Requests\BusUpdateRequest;
use App\Models\Bus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BusController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Bus::class);

        $query = Bus::with('latestKmRecord');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('dqn', 'ILIKE', "%{$request->search}%")
                    ->orWhere('route_number', 'ILIKE', "%{$request->search}%")
                    ->orWhere('bus_project', 'ILIKE', "%{$request->search}%");
            });
        }

        $buses = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => $buses->items(),
            'meta' => [
                'total' => $buses->total(),
                'per_page' => $buses->perPage(),
                'current_page' => $buses->currentPage(),
                'last_page' => $buses->lastPage(),
            ],
        ]);
    }

    public function store(BusStoreRequest $request)
    {
        Gate::authorize('create', Bus::class);

        $data = $request->validated();
        $data['date'] = now()->format('Y-m-d');
        $data = $this->addGarageContext($data);

        $bus = Bus::create($data);

        return response()->json([
            'message' => 'Avtobus uğurla əlavə edildi!',
            'data' => $bus,
        ], 201);
    }

    public function show(Bus $bus)
    {
        Gate::authorize('view', $bus);

        return response()->json([
            'data' => $bus->load(['latestKmRecord', 'dailyKmRecords' => function ($q) {
                $q->orderBy('date', 'desc')->limit(10);
            }]),
        ]);
    }

    public function update(BusUpdateRequest $request, Bus $bus)
    {
        Gate::authorize('update', $bus);

        $data = $request->validated();
        $bus->update($data);

        return response()->json([
            'message' => 'Avtobus uğurla yeniləndi!',
            'data' => $bus->fresh(),
        ]);
    }

    public function destroy(Bus $bus)
    {
        Gate::authorize('delete', $bus);

        $bus->delete();

        return response()->json([
            'message' => 'Avtobus uğurla silindi!',
        ]);
    }

    public function search(Request $request)
    {
        Gate::authorize('viewAny', Bus::class);

        $query = Bus::with('latestKmRecord');

        if ($request->dqn) {
            $query->where('dqn', 'ILIKE', "%{$request->dqn}%");
        }
        if ($request->route_number) {
            $query->where('route_number', 'ILIKE', "%{$request->route_number}%");
        }
        if ($request->bus_project) {
            $query->where('bus_project', 'ILIKE', "%{$request->bus_project}%");
        }
        if ($request->vin) {
            $query->where('vin', 'ILIKE', "%{$request->vin}%");
        }

        $buses = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => $buses->items(),
            'meta' => [
                'total' => $buses->total(),
                'per_page' => $buses->perPage(),
                'current_page' => $buses->currentPage(),
                'last_page' => $buses->lastPage(),
            ],
        ]);
    }
}
