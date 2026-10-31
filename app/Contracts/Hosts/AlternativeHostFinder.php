<?php

namespace App\Contracts\Hosts;

use App\Modules\Booking\Models\Booking;
use Illuminate\Support\Collection;

interface AlternativeHostFinder
{
    /** @return Collection<int, int> Host user IDs. */
    public function find(Booking $booking): Collection;
}
