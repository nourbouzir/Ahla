<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::query()->with('actor')
            ->when($request->query('entity'), fn ($q, $entity) => $q->where('entity', $entity))
            ->latest('created_at')->latest('id')->paginate(50)->withQueryString();

        return view('admin::audit', compact('logs'));
    }
}
