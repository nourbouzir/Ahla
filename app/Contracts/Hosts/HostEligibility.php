<?php

namespace App\Contracts\Hosts;

interface HostEligibility
{
    public function isBookable(int $hostId): bool;
}
