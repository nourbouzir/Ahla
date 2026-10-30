<?php

namespace App\Modules\Auth\Enums;

/**
 * Verification status of a host (SRS FV FR-06, ADR 0004 decision 2). Only the
 * values live here; which transitions are allowed belongs to the HostState
 * classes of the Verification module.
 *
 * Verification requests reuse Verified and Rejected as their decision.
 */
enum HostStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Suspended = 'suspended';
}
