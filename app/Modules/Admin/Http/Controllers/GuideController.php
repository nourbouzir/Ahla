<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Services\AdminStats;
use App\Modules\Verification\Enums\HostStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuideController extends Controller
{
    public function __invoke(Request $request, AdminStats $stats): View
    {
        $status = HostStatus::tryFrom((string) $request->query('status'))?->value;
        $city = array_key_exists((string) $request->query('city'), config('ahla.cities')) ? $request->query('city') : null;
        $q = trim((string) $request->query('q'));

        return view('admin::guides', [
            'guides' => $stats->guides($status, $city, $q !== '' ? $q : null),
            'status' => $status,
            'city' => $city,
            'q' => $q,
            'cities' => array_keys(config('ahla.cities')),
            'all' => $stats->guides(),
        ]);
    }
}
