<?php

namespace App\Modules\Booking\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Modules\Booking\Services\HostDashboard;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class EarningsController extends Controller
{
    public function __construct(private readonly HostDashboard $dashboard) {}

    private function month(Request $request): ?string
    {
        $month = (string) $request->query('month');

        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) ? $month : null;
    }

    public function index(Request $request): View
    {
        $month = $this->month($request);
        $rows = $this->dashboard->earnings($request->user(), $month);

        return view('booking::host.earnings', [
            'rows' => $rows,
            'month' => $month,
            'gross' => $rows->reduce(fn ($s, $r) => bcadd($s, $r['gross'], 3), '0.000'),
            'refunded' => $rows->reduce(fn ($s, $r) => bcadd($s, $r['refunded'], 3), '0.000'),
            'net' => $rows->reduce(fn ($s, $r) => bcadd($s, $r['net'], 3), '0.000'),
            'months' => $this->dashboard->months($request->user(), 6),
        ]);
    }

    public function csv(Request $request): Response
    {
        $rows = $this->dashboard->earnings($request->user(), $this->month($request));
        $out = fopen('php://temp', 'w+');
        fputcsv($out, ['date', 'experience', 'client', 'gross_tnd', 'refunded_tnd', 'net_tnd']);

        // A leading = + - @ would be run as a formula by spreadsheets: neutralise it.
        $safe = fn (string $v) => preg_match('/^[=+\-@]/', $v) ? "'".$v : $v;

        foreach ($rows as $r) {
            fputcsv($out, [$r['booking']->date->toDateString(), $safe($r['booking']->experience->title), $safe($r['booking']->tourist->name), $r['gross'], $r['refunded'], $r['net']]);
        }

        rewind($out);

        return response(stream_get_contents($out), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="ahla-earnings.csv"',
        ]);
    }
}
