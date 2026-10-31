<?php

namespace App\Contracts\Fakes;

enum FakeGatewayMode: string
{
    case Success = 'success';
    case Decline = 'decline';
    case Timeout = 'timeout';
}
