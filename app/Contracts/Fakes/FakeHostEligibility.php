<?php

namespace App\Contracts\Fakes;

use App\Contracts\Hosts\HostEligibility;

class FakeHostEligibility implements HostEligibility
{
    public function __construct(private bool $eligible = false) {}

    public function setEligible(bool $eligible): void
    {
        $this->eligible = $eligible;
    }

    public function isBookable(int $hostId): bool
    {
        return $this->eligible;
    }
}
