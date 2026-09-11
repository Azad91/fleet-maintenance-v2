<?php

namespace App\Http\Controllers\Reports;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DailyKmReportController extends ReportController
{
    public function missing(Request $request): View        { return $this->render('missing'); }
    public function topBuses(Request $request): View       { return $this->render('top-buses'); }
    public function workerActivity(Request $request): View { return $this->render('worker-activity'); }

    private function render(string $view): View
    {
        $this->scope('daily_km');

        return view("reports.daily-km.{$view}", [
            'domain' => 'daily_km',
            'activeReport' => $view,
        ]);
    }
}
