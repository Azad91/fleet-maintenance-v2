<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Models\Complaint;
use App\Models\ComplaintItem;
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

        // 2. Son 5 avtobus
        $recentBuses = Bus::orderBy('id', 'desc')->limit(5)->get();

        // 3. Kritik stok (5-dən az)
        $lowStockItems = Warehouse::where('miqdar', '<', 5)
            ->orderBy('miqdar', 'asc')
            ->limit(10)
            ->get();

        // 4. Açıq şikayətlər
        $recentComplaints = Complaint::with('bus')
            ->where('status', '!=', 'həll olundu')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        // 5. ✅ DÜZƏLİŞ: Təkrarlanan nasazlıqlar (complaint_items cədvəlindən)
        $recurringIssues = ComplaintItem::select(
                'complaint_items.description',
                'complaints.bus_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('MAX(complaints.created_at) as last_occurrence')
            )
            ->join('complaints', 'complaints.id', '=', 'complaint_items.complaint_id')
            ->where('complaints.created_at', '>=', now()->subDays(30))
            ->where('complaints.status', '!=', 'həll olundu')
            ->where('complaints.garage_id', \App\Models\Garage::getCurrentId())
            ->groupBy('complaint_items.description', 'complaints.bus_id')
            ->having(DB::raw('COUNT(*)'), '>=', 2)
            ->with('complaint.bus')
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
