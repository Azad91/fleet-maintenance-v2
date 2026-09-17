<?php

namespace App\Http\Controllers;

use App\Enums\TransferType;
use App\Http\Requests\WarehouseTransferReceiveRequest;
use App\Http\Requests\WarehouseTransferStoreRequest;
use App\Models\Garage;
use App\Models\ServiceVehicle;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Services\GarageContext;
use App\Services\Warehouse\WarehouseTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class WarehouseTransferController extends Controller
{
    public function __construct(
        protected WarehouseTransferService $service
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', WarehouseTransfer::class);

        $garageId = GarageContext::getGarageId();

        $direction = $request->input('direction', 'all');
        $status    = $request->input('status');

        $query = WarehouseTransfer::query()
            ->with(['fromGarage', 'toGarage', 'toServiceVehicle', 'items'])
            ->visibleToGarage($garageId)
            ->orderByDesc('id');

        if ($direction === 'outbound') {
            $query->outbound($garageId);
        } elseif ($direction === 'inbound') {
            $query->inbound($garageId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $transfers = $query->paginate(config('settings.pagination', 25))->withQueryString();

        return view('warehouse-transfers.index', compact('transfers', 'direction', 'status'));
    }

    public function create(): View
    {
        $this->authorize('create', WarehouseTransfer::class);

        $garageId = GarageContext::getGarageId();
        $companyId = GarageContext::getCompanyId();

        $warehouses = Warehouse::query()->orderBy('name')->get();

        // Other garages within the same company (excluding self).
        $otherGarages = Garage::where('company_id', $companyId)
            ->where('id', '!=', $garageId)
            ->orderBy('name')
            ->get();

        $serviceVehicles = ServiceVehicle::active()->orderBy('name')->get();

        return view('warehouse-transfers.create', compact('warehouses', 'otherGarages', 'serviceVehicles'));
    }

    public function store(WarehouseTransferStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', WarehouseTransfer::class);

        $type = TransferType::from($request->input('type'));
        $garageId = GarageContext::getGarageId();
        $companyId = GarageContext::getCompanyId();

        try {
            // Full workflow (draft → dispatch → receive) for
            // cross-garage transfers.
            if ($type->requiresWorkflow()) {
                $transfer = $this->service->create(
                    $request->validated() + ['from_garage_id' => $garageId],
                    $companyId
                );

                $message = __('messages.transfers.created');
            } else {
                // Immediate completion for quarantine and (Phase 3.2)
                // service vehicle transfers.
                $transfer = $this->service->createAndComplete(
                    $request->validated() + ['from_garage_id' => $garageId],
                    $companyId
                );

                $message = __('messages.transfers.completed');
            }
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('warehouse-transfers.show', $transfer)
            ->with('success', $message);
    }

    public function show(WarehouseTransfer $transfer): View
    {
        $this->authorize('view', $transfer);

        $transfer->load([
            'fromGarage', 'toGarage', 'toServiceVehicle',
            'items.warehouse', 'dispatchedBy', 'receivedBy', 'resolvedBy', 'creator',
        ]);

        return view('warehouse-transfers.show', compact('transfer'));
    }

    public function dispatch(WarehouseTransfer $transfer): RedirectResponse
    {
        $this->authorize('dispatch', $transfer);

        try {
            $this->service->dispatch($transfer);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('warehouse-transfers.show', $transfer)
            ->with('success', __('messages.transfers.dispatched'));
    }

    public function receive(WarehouseTransferReceiveRequest $request, WarehouseTransfer $transfer): RedirectResponse
    {
        $this->authorize('receive', $transfer);

        try {
            $this->service->receive($transfer, $request->input('received'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('warehouse-transfers.show', $transfer)
            ->with('success', __('messages.transfers.received'));
    }

    public function reject(Request $request, WarehouseTransfer $transfer): RedirectResponse
    {
        $this->authorize('reject', $transfer);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:2000',
        ]);

        try {
            $this->service->reject($transfer, $validated['reason'] ?? null);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('warehouse-transfers.show', $transfer)
            ->with('success', __('messages.transfers.rejected'));
    }

    public function resolve(Request $request, WarehouseTransfer $transfer): RedirectResponse
    {
        $this->authorize('resolve', $transfer);

        $validated = $request->validate([
            'resolution' => 'required|in:retransfer,loss_accepted',
        ]);

        try {
            $this->service->resolveDisputed($transfer, $validated['resolution']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('warehouse-transfers.show', $transfer)
            ->with('success', __('messages.transfers.resolved'));
    }

    public function cancel(WarehouseTransfer $transfer): RedirectResponse
    {
        $this->authorize('cancel', $transfer);

        try {
            $this->service->cancel($transfer);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('warehouse-transfers.index')
            ->with('success', __('messages.transfers.cancelled'));
    }
}
