<?php

namespace App\Modules\Auth\Enums;

enum Role: string
{
    case Tourist = 'tourist';
    case Host = 'host';
    case Admin = 'admin';
}
