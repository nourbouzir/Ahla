<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Services\AdminStats;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class RevenueController extends Controller
{
    public function __construct(private readonly AdminStats $stats) {}

    public function index(Request $request): View
    {
        $months = $this->stats->months(12);
        $totals = collect($months)->reduce(fn ($t, $m) => [
            'paid' => bcadd($t['paid'], $m['paid'], 3),
            'refunded' => bcadd($t['refunded'], $m['refunded'], 3),
            'volume' => bcadd($t['volume'], $m['volume'], 3),
            'revenue' => bcadd($t['revenue'], $m['revenue'], 3),
            'bookings' => $t['bookings'] + $m['bookings'],
        ], ['paid' => '0.000', 'refunded' => '0.000', 'volume' => '0.000', 'revenue' => '0.000', 'bookings' => 0]);

        return view('admin::revenue', ['months' => $months, 'totals' => $totals, 'rate' => $this->stats->rate()] + $this->stats->breakdown());
    }

    public function csv(): Response
    {
        $out = fopen('php://temp', 'w+');
        fputcsv($out, ['month', 'payments', 'paid_tnd', 'refunded_tnd', 'volume_tnd', 'indicative_revenue_tnd']);

        foreach ($this->stats->months(12) as $m) {
            fputcsv($out, [$m['month'], $m['bookings'], $m['paid'], $m['refunded'], $m['volume'], $m['revenue']]);
        }

        rewind($out);

        return response(stream_get_contents($out), 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="ahla-revenue.csv"']);
    }
}
