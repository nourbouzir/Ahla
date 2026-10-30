<?php

namespace App\Modules\Auth\Enums;

/** What a user is allowed to do on AHLA. Stored as a string in users.role. */
enum Role: string
{
    case Tourist = 'tourist';
    case Host = 'host';
    case Admin = 'admin';
}
