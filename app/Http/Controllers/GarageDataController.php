<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Models\BusServiceInterval;
use App\Models\Driver;
use App\Models\MotorOilDetail;
use App\Models\ServiceTemplate;
use App\Models\Warehouse;
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
            'dqn'    => $bus?->dqn,
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

    public function serviceTemplates(int $busId)
    {
        $bus = Bus::findOrFail($busId);

        $templates = Cache::remember('service_templates', 3600, function () {
            return ServiceTemplate::orderBy('default_km_interval')->get();
        });

        $intervals = BusServiceInterval::where('bus_id', $bus->id)
            ->whereIn('service_template_id', $templates->pluck('id'))
            ->get()
            ->keyBy('service_template_id');

        return response()->json($templates->map(fn (ServiceTemplate $template) => [
            'id'          => $template->id,
            'name'        => $template->name,
            'km_interval' => $intervals->get($template->id)?->custom_km_interval ?? $template->default_km_interval,
            'details'     => $template->details,
        ])->values());
    }

    public function motorOilServices(int $busId)
    {
        $bus = Bus::findOrFail($busId);
        $latestKm = $bus->dailyKmRecords()->latest('date')->value('km') ?? $bus->km ?? 0;

        $motorOils = Cache::remember('motor_oil_details', 3600, function () {
            return MotorOilDetail::orderBy('km')->orderBy('part_name')->get();
        });

        return response()->json(
            $motorOils->where('km', '>', $latestKm)
                ->groupBy('km')
                ->map(fn ($details, $km) => [
                    'km'      => (int) $km,
                    'details' => $details->map(fn (MotorOilDetail $detail) => [
                        'part_code' => $detail->part_code,
                        'part_name' => $detail->part_name,
                        'quantity'  => $detail->quantity,
                        'count'     => $detail->count,
                        'unit'      => $detail->unit,
                    ])->values(),
                ])->values()
        );
    }

    public function driverByCode(string $code)
    {
        $driver = Driver::active()
            ->where('code', mb_strtoupper(trim($code)))
            ->first();

        return response()->json([
            'driver_name' => $driver?->full_name,
            'driver_id'   => $driver?->id,
            'found'       => (bool) $driver,
        ]);
    }
}