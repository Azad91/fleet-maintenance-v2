<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WarehouseStoreRequest;
use App\Http\Requests\WarehouseUpdateRequest;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Warehouse::class);

        $perPage = min((int) $request->input('per_page', 15), 100);

        $query = Warehouse::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'ILIKE', "%{$request->search}%")
                    ->orWhere('name', 'ILIKE', "%{$request->search}%");
            });
        }

        $warehouses = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $warehouses->items(),
            'meta' => [
                'total' => $warehouses->total(),
                'per_page' => $warehouses->perPage(),
                'current_page' => $warehouses->currentPage(),
                'last_page' => $warehouses->lastPage(),
            ],
        ]);
    }

    public function store(WarehouseStoreRequest $request)
    {
        Gate::authorize('create', Warehouse::class);

        $data = $request->validated();
        $data = $this->addGarageContext($data);

        $warehouse = Warehouse::create($data);

        return response()->json([
            'message' => 'Anbar məlumatı uğurla əlavə edildi!',
            'data' => $warehouse,
        ], 201);
    }

    public function show(Warehouse $warehouse)
    {
        Gate::authorize('view', $warehouse);

        return response()->json(['data' => $warehouse]);
    }

    public function update(WarehouseUpdateRequest $request, Warehouse $warehouse)
    {
        Gate::authorize('update', $warehouse);

        $warehouse->update($request->validated());

        return response()->json([
            'message' => 'Anbar məlumatı uğurla yeniləndi!',
            'data' => $warehouse->fresh(),
        ]);
    }

    public function destroy(Warehouse $warehouse)
    {
        Gate::authorize('delete', $warehouse);

        $warehouse->delete();

        return response()->json([
            'message' => 'Anbar məlumatı uğurla silindi!',
        ]);
    }

    public function search(Request $request)
    {
        Gate::authorize('viewAny', Warehouse::class);

        $query = Warehouse::query();

        if ($request->code) {
            $query->where('code', 'ILIKE', "%{$request->code}%");
        }
        if ($request->name) {
            $query->where('name', 'ILIKE', "%{$request->name}%");
        }

        $warehouses = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => $warehouses->items(),
            'meta' => [
                'total' => $warehouses->total(),
                'per_page' => $warehouses->perPage(),
                'current_page' => $warehouses->currentPage(),
                'last_page' => $warehouses->lastPage(),
            ],
        ]);
    }
}
