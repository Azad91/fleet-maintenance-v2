<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Models\BusServiceInterval;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\MotorOilDetail;
use App\Models\ServiceTemplate;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GarageDataController extends Controller
{
    public function busByLine(string $identifier)
    {
        $bus = Bus::where(function ($query) use ($identifier) {
            $query->where('route_number', $identifier)
                ->orWhere('dqn', $identifier);

            if (is_numeric($identifier)) {
                $query->orWhere('id', (int) $identifier);
            }
        })->first();

        return response()->json([
            'dqn' => $bus?->dqn,
            'bus_id' => $bus?->id,
        ]);
    }

    public function detailByCode(string $code)
    {
        $detail = Warehouse::where('code', $code)->first();

        return response()->json([
            'detallar_name' => $detail?->name,
            'stock_quantity' => $detail?->quantity,
        ]);
    }

    public function busKm(int $busId)
    {
        $bus = Bus::findOrFail($busId);
        $latestKm = $bus->dailyKmRecords()->latest('date')->value('km');

        return response()->json(['km' => $latestKm ?? $bus->km]);
    }

    public function busByDqn(string $dqn)
    {
        $bus = Bus::where('dqn', $dqn)->first();

        if (! $bus) {
            return response()->json([
                'found' => false,
            ]);
        }

        $latestKm = $bus->dailyKmRecords()->value('km') ?? $bus->km;

        return response()->json([
            'found' => true,
            'bus_id' => $bus->id,
            'dqn' => $bus->dqn,
            'route_number' => $bus->route_number,
            'km' => $latestKm,
        ]);
    }

    public function serviceTemplates(int $busId)
    {
        $bus = Bus::findOrFail($busId);

        // Cache key MUST include garage_id AND brand_id — otherwise
        // the first brand's template list would be served to every
        // other brand in the same garage for the next hour.
        $brandKey = $bus->brand_id ?? 'none';
        $cacheKey = "service_templates:garage:{$bus->garage_id}:brand:{$brandKey}";

        $templates = Cache::remember($cacheKey, 3600, function () use ($bus) {
            return ServiceTemplate::where('brand_id', $bus->brand_id)
                ->orderBy('default_km_interval')
                ->get();
        });

        $intervals = BusServiceInterval::where('bus_id', $bus->id)
            ->whereIn('service_template_id', $templates->pluck('id'))
            ->get()
            ->keyBy('service_template_id');

        return response()->json($templates->map(fn (ServiceTemplate $template) => [
            'id' => $template->id,
            'name' => $template->name,
            'km_interval' => $intervals->get($template->id)?->custom_km_interval ?? $template->default_km_interval,
            'details' => $template->details,
        ])->values());
    }

    public function motorOilServices(int $busId)
    {
        $bus = Bus::findOrFail($busId);
        $latestKm = $bus->dailyKmRecords()->latest('date')->value('km') ?? $bus->km ?? 0;

        // Cache key MUST include brand_id — same reasoning as above.
        $brandKey = $bus->brand_id ?? 'none';
        $cacheKey = "motor_oil_details:garage:{$bus->garage_id}:brand:{$brandKey}";

        $motorOils = Cache::remember($cacheKey, 3600, function () use ($bus) {
            return MotorOilDetail::where('brand_id', $bus->brand_id)
                ->orderBy('km')
                ->orderBy('part_name')
                ->get();
        });

        return response()->json(
            $motorOils->where('km', '>', $latestKm)
                ->groupBy('km')
                ->map(fn ($details, $km) => [
                    'km' => (int) $km,
                    'details' => $details->map(fn (MotorOilDetail $detail) => [
                        'part_code' => $detail->part_code,
                        'part_name' => $detail->part_name,
                        'quantity' => $detail->quantity,
                        'count' => $detail->count,
                        'unit' => $detail->unit,
                    ])->values(),
                ])->values()
        );
    }

    public function motorOilIntervals(int $busId)
    {
        $bus = Bus::findOrFail($busId);

        $currentKm = (int) ($bus->dailyKmRecords()->value('km') ?? $bus->km ?? 0);

        // Filter intervals by the bus's brand — see class docblock.
        $intervals = MotorOilDetail::where('brand_id', $bus->brand_id)
            ->select('km')
            ->distinct()
            ->orderBy('km')
            ->pluck('km')
            ->map(fn ($km) => (int) $km)
            ->all();

        // Closest interval in the past
        $past = null;
        foreach ($intervals as $km) {
            if ($km <= $currentKm) {
                $past = $km;
            } else {
                break;
            }
        }

        // Next interval in the future
        $next = null;
        foreach ($intervals as $km) {
            if ($km > $currentKm) {
                $next = $km;
                break;
            }
        }

        $result = [];

        if ($past !== null) {
            $result[] = [
                'km' => $past,
                'label' => __('messages.complaints.motor_oil_service_label', ['km' => number_format($past, 0, '', '')]),
            ];
        }

        if ($next !== null) {
            $result[] = [
                'km' => $next,
                'label' => __('messages.complaints.motor_oil_service_label', ['km' => number_format($next, 0, '', '')]),
            ];
        }

        return response()->json([
            'current_km' => $currentKm,
            'intervals' => $result,
        ]);
    }

    public function motorOilParts(Request $request, int $busId)
    {
        $bus = Bus::findOrFail($busId);
        $km = (int) $request->query('km');

        if ($km <= 0) {
            return response()->json(['parts' => []]);
        }

        // Filter parts by the bus's brand.
        $parts = MotorOilDetail::where('brand_id', $bus->brand_id)
            ->where('km', $km)
            ->orderBy('part_name')
            ->get()
            ->map(function (MotorOilDetail $part) use ($bus) {
                $warehouse = Warehouse::withoutGlobalScopes()
                    ->where('code', $part->part_code)
                    ->where('garage_id', $bus->garage_id)
                    ->whereNull('deleted_at')
                    ->first();

                return [
                    'part_code' => $part->part_code,
                    'part_name' => $part->part_name,
                    'unit' => $part->unit,
                    'quantity' => (float) $part->quantity,
                    'stock_quantity' => $warehouse?->quantity ?? 0,
                ];
            });

        return response()->json(['parts' => $parts]);
    }

    public function driverByCode(string $code)
    {
        $driver = Driver::active()
            ->where('code', mb_strtoupper(trim($code)))
            ->first();

        return response()->json([
            'driver_name' => $driver?->full_name,
            'driver_id' => $driver?->id,
            'found' => (bool) $driver,
        ]);
    }

    public function serviceVehiclePartByCode(Request $request)
    {
        $request->validate([
            'service_vehicle_id' => 'required|integer',
            'code' => 'required|string',
        ]);

        $stock = \App\Models\ServiceVehicleStock::withoutGlobalScopes()
            ->where('service_vehicle_id', $request->integer('service_vehicle_id'))
            ->where('code', $request->string('code'))
            ->first();

        return response()->json([
            'found' => (bool) $stock,
            'part_code' => $stock?->code,
            'part_name' => $stock?->name,
            'stock_quantity' => $stock?->quantity ?? 0,
            'unit' => $stock?->unit,
        ]);
    }

    public function employeeByCode(string $kod)
    {
        $employee = Employee::active()
            ->where('code', mb_strtoupper(trim($kod)))
            ->first();

        return response()->json([
            'employee_id' => $employee?->id,
            'employee_name' => $employee?->full_name_with_position,
            'found' => (bool) $employee,
        ]);
    }
}
