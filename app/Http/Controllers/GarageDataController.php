<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Models\BusServiceInterval;
use App\Models\Driver;
use App\Models\MotorOilDetail;
use App\Models\ServiceTemplate;
use App\Models\Warehouse;

class GarageDataController extends Controller
{
    public function busByLine(string $routeNumber)  // dəyişdi (əvvəl: xettNo)
    {
        $bus = Bus::where('route_number', $routeNumber)->first();  // dəyişdi

        return response()->json([
            'dqn' => $bus?->dqn,
            'bus_id' => $bus?->id,
        ]);
    }

    public function detailByCode(string $code)  // dəyişdi (əvvəl: kod)
    {
        $detail = Warehouse::where('code', $code)->first();  // dəyişdi

        return response()->json([
            'detal_adi' => $detail?->name,  // dəyişdi (əvvəl: ad)
            'depo_miqdari' => $detail?->quantity,  // dəyişdi
        ]);
    }

    public function busKm(int $busId)
    {
        $bus = Bus::findOrFail($busId);
        $latestKm = $bus->dailyKmRecords()->latest('date')->value('km');  // dəyişdi

        return response()->json(['km' => $latestKm ?? $bus->km]);
    }

    public function serviceTemplates(int $busId)
    {
        $bus = Bus::findOrFail($busId);
        $templates = ServiceTemplate::orderBy('default_km_interval')->get();
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
        $latestKm = $bus->dailyKmRecords()->latest('date')->value('km') ?? $bus->km ?? 0;  // dəyişdi

        return response()->json(
            MotorOilDetail::where('km', '>', $latestKm)
                ->orderBy('km')
                ->orderBy('part_name')  // dəyişdi (əvvəl: detal_adi)
                ->get()
                ->groupBy('km')
                ->map(fn ($details, $km) => [
                    'km' => (int) $km,
                    'details' => $details->map(fn (MotorOilDetail $detail) => [
                        'kodu' => $detail->part_code,  // dəyişdi
                        'adi' => $detail->part_name,    // dəyişdi
                        'miqdar' => $detail->quantity,  // dəyişdi
                        'say' => $detail->count,        // dəyişdi
                        'olcu_vahidi' => $detail->unit, // dəyişdi
                    ])->values(),
                ])->values()
        );
    }

    public function driverByCode(string $code)  // dəyişdi (əvvəl: kod)
    {
        $driver = Driver::active()
            ->where('code', mb_strtoupper(trim($code)))  // dəyişdi
            ->first();

        return response()->json([
            'driver_ad' => $driver?->full_name,
            'driver_id' => $driver?->id,
            'found' => (bool) $driver,
        ]);
    }
}
