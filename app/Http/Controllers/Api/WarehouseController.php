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

        $perPage = max(1, min((int) $request->input('per_page', 15), 100));

        $query = Warehouse::query();

        if ($request->search) {
            $search = addcslashes($request->search, '%_\\');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'ILIKE', "%{$search}%")
                    ->orWhere('name', 'ILIKE', "%{$search}%");
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

        // HasGarageScope auto-populates garage_id and company_id on
        // create, so no explicit context injection is needed here.
        $warehouse = Warehouse::create($request->validated());

        return response()->json([
            'message' => __('messages.flash.created', ['Item' => 'Warehouse item']),
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
            'message' => __('messages.flash.updated', ['Item' => 'Warehouse item']),
            'data' => $warehouse->fresh(),
        ]);
    }

    public function destroy(Warehouse $warehouse)
    {
        Gate::authorize('delete', $warehouse);

        $warehouse->delete();

        return response()->json([
            'message' => __('messages.flash.deleted', ['Item' => 'Warehouse item']),
        ]);
    }

    public function search(Request $request)
    {
        Gate::authorize('viewAny', Warehouse::class);

        $perPage = max(1, min((int) $request->input('per_page', 15), 100));

        $query = Warehouse::query();

        if ($request->code) {
            $escapedCode = addcslashes($request->code, '%_\\');
            $query->where('code', 'ILIKE', "%{$escapedCode}%");
        }
        if ($request->name) {
            $escapedName = addcslashes($request->name, '%_\\');
            $query->where('name', 'ILIKE', "%{$escapedName}%");
        }

        $warehouses = $query->paginate($perPage);

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
