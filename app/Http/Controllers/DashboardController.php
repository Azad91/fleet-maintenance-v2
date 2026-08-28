<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Models\Complaint;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Statistik məlumatlar (kartlar üçün)
        $totalBuses = Bus::count();
        $activeBuses = Bus::where('aktiv', true)->count();
        $activeComplaints = Complaint::where('status', '!=', 'həll olundu')->count();
        $totalWarehouseItems = Warehouse::sum('miqdar');

        // 2. Son 5 avtobus (həmişə göstərilir)
        $recentBuses = Bus::orderBy('id', 'desc')->limit(5)->get();

        // 3. Kritik stok (5-dən az)
        $lowStockItems = Warehouse::where('miqdar', '<', 5)
            ->orderBy('miqdar', 'asc')
            ->limit(10)
            ->get();

        // 4. Açıq şikayətlər (bağlanmamış) - 🔥 YENİ ƏLAVƏ
        $recentComplaints = Complaint::with('bus')
            ->where('status', '!=', 'həll olundu')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        // 5. Təkrarlanan nasazlıqlar
        $recurringIssues = Complaint::select(
                'bus_id',
                'shikayet',
                DB::raw('COUNT(*) as total'),
                DB::raw('MAX(created_at) as last_occurrence')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->where('status', '!=', 'həll olundu')
            ->groupBy('bus_id', 'shikayet')
            ->having(DB::raw('COUNT(*)'), '>=', 2)
            ->with('bus')
            ->get();

        // 6. Bu gün KM daxil edilməyən avtobuslar
        $today = now()->toDateString();
        $busesWithoutKmToday = Bus::whereDoesntHave('dailyKmRecords', function ($query) use ($today) {
            $query->whereDate('tarix', $today);
        })->get();

        return view('dashboard', compact(
            'totalBuses',
            'activeBuses',
            'activeComplaints',
            'totalWarehouseItems',
            'recentBuses',
            'lowStockItems',
            'recentComplaints',
            'recurringIssues',
            'busesWithoutKmToday'
        ));
    }
}
