<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Services\AdminStats;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(AdminStats $stats): View
    {
        return view('admin::dashboard', $stats->overview());
    }
}
