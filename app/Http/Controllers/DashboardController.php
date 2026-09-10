<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Models\Complaint;
use App\Models\ComplaintItem;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', DashboardController::class);

        $totalBuses = Bus::count();
        $activeBuses = Bus::where('is_active', true)->count();
        $activeComplaints = Complaint::where('status', '!=', 'completed')->count();
        $totalWarehouseItems = Warehouse::sum('quantity');

        $recentBuses = Bus::orderBy('id', 'desc')->limit(5)->get();

        $lowStockItems = Warehouse::where('quantity', '<', 5)
            ->orderBy('quantity', 'asc')
            ->limit(10)
            ->get();

        $recentComplaints = Complaint::with('bus', 'items')
            ->where('status', '!=', 'completed')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        $recurringIssues = ComplaintItem::recurring(30)->get();

        $today = now()->toDateString();
        $busesWithoutKmTodayCount = Bus::whereDoesntHave('dailyKmRecords', function ($query) use ($today) {
            $query->whereDate('date', $today);
        })->count();

        $busesWithoutKmToday = Bus::whereDoesntHave('dailyKmRecords', function ($query) use ($today) {
            $query->whereDate('date', $today);
        })->limit(10)->get();

        return view('dashboard', compact(
            'totalBuses',
            'activeBuses',
            'activeComplaints',
            'totalWarehouseItems',
            'recentBuses',
            'lowStockItems',
            'recentComplaints',
            'recurringIssues',
            'busesWithoutKmToday',
            'busesWithoutKmTodayCount'
        ));
    }
}