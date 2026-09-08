<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusStoreRequest;
use App\Http\Requests\BusUpdateRequest;
use App\Models\Bus;
use App\Services\BusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BusController extends Controller
{
    public function __construct(protected BusService $busService) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Bus::class);

        // ✅ Maksimum 100 element
        $perPage = min((int) $request->input('per_page', 15), 100);

        $buses = $this->busService->getPaginatedBuses($request->search, $perPage);

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

    public function store(BusStoreRequest $request): JsonResponse
    {
        Gate::authorize('create', Bus::class);

        $bus = $this->busService->createBus($request->validated());

        return response()->json([
            'message' => 'Avtobus uğurla əlavə edildi!',
            'data' => $bus,
        ], 201);
    }

    public function show(Bus $bus): JsonResponse
    {
        Gate::authorize('view', $bus);

        return response()->json([
            'data' => $bus->load(['latestKmRecord', 'dailyKmRecords' => function ($q) {
                $q->orderBy('date', 'desc')->limit(10);
            }]),
        ]);
    }

    public function update(BusUpdateRequest $request, Bus $bus): JsonResponse
    {
        Gate::authorize('update', $bus);

        $updatedBus = $this->busService->updateBus($bus, $request->validated());

        return response()->json([
            'message' => 'Avtobus uğurla yeniləndi!',
            'data' => $updatedBus,
        ]);
    }

    public function destroy(Bus $bus): JsonResponse
    {
        Gate::authorize('delete', $bus);

        $this->busService->deleteBus($bus);

        return response()->json([
            'message' => 'Avtobus uğurla silindi!',
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Bus::class);

        $perPage = min((int) $request->input('per_page', 15), 100);
        $buses = $this->busService->advancedSearch($request->all(), $perPage);
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
