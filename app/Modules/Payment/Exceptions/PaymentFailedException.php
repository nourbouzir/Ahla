<?php

namespace App\Modules\Payment\Exceptions;

use RuntimeException;

/** The gateway declined, timed out or answered with something that is not success. */
class PaymentFailedException extends RuntimeException {}
