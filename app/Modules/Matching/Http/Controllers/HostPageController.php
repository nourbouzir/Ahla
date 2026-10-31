<?php

namespace App\Modules\Matching\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Verification\Enums\HostStatus;
use App\Modules\Verification\Models\HostProfile;
use Illuminate\View\View;

class HostPageController extends Controller
{
    public function show(HostProfile $host): View
    {
        // Only verified hosts have a public page (FR-07).
        abort_unless($host->status === HostStatus::Verified, 404);

        $host->load([
            'user.languages', 'user.interests', 'availabilities',
            'experiences' => fn ($q) => $q->where('published', true)->orderBy('id'),
        ]);

        return view('matching::host', ['host' => $host]);
    }
}
