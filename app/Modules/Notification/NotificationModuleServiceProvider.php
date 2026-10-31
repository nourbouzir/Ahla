<?php

namespace App\Modules\Notification;

use App\Events\BookingCancelled;
use App\Events\BookingConfirmed;
use App\Events\BookingRequested;
use App\Events\BookingStatusChanged;
use App\Events\MessageSent;
use App\Events\PaymentStatusChanged;
use App\Events\RefundRequested;
use App\Modules\ModuleServiceProvider;
use App\Modules\Notification\Services\NotificationDispatcher;
use Illuminate\Support\Facades\Event;

class NotificationModuleServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        // The Notification module only listens; it never calls into other modules' services.
        foreach ([
            BookingRequested::class => 'bookingRequested',
            BookingConfirmed::class => 'bookingConfirmed',
            BookingStatusChanged::class => 'bookingStatusChanged',
            BookingCancelled::class => 'bookingCancelled',
            PaymentStatusChanged::class => 'paymentChanged',
            RefundRequested::class => 'refundRequested',
            MessageSent::class => 'messageSent',
        ] as $event => $method) {
            Event::listen($event, [NotificationDispatcher::class, $method]);
        }
    }
}
