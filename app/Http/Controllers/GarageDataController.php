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
    public function busByLine(string $xettNo)
    {
        $bus = Bus::where('xett_no', $xettNo)->first();

        return response()->json([
            'dqn' => $bus?->dqn,
            'bus_id' => $bus?->id,
        ]);
    }

    public function detailByCode(string $kod)
    {
        $detail = Warehouse::where('kod', $kod)->first();

        return response()->json([
            'detal_adi' => $detail?->ad,
            'depo_miqdari' => $detail?->miqdar,
        ]);
    }

    public function busKm(int $busId)
    {
        $bus = Bus::findOrFail($busId);
        $latestKm = $bus->dailyKmRecords()->latest('tarix')->value('km');

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
        $latestKm = $bus->dailyKmRecords()->latest('tarix')->value('km') ?? $bus->km ?? 0;

        return response()->json(
            MotorOilDetail::where('km', '>', $latestKm)
                ->orderBy('km')
                ->orderBy('detal_adi')
                ->get()
                ->groupBy('km')
                ->map(fn ($details, $km) => [
                    'km' => (int) $km,
                    'details' => $details->map(fn (MotorOilDetail $detail) => [
                        'kodu' => $detail->detal_kodu,
                        'adi' => $detail->detal_adi,
                        'miqdar' => $detail->miqdar,
                        'say' => $detail->say,
                    ])->values(),
                ])->values()
        );
    }

    public function driverByCode(string $kod)
    {
        $driver = Driver::where('kodu', $kod)->first();

        return response()->json([
            'driver_ad' => $driver?->full_name,
            'driver_id' => $driver?->id,
        ]);
    }
}
