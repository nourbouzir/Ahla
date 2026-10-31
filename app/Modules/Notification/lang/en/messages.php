<?php

return [
    'title' => 'Notifications', 'read_all' => 'Mark all as read', 'new' => 'New', 'none' => 'No notifications yet.',
    'open' => 'Open', 'saved' => 'Saved.', 'email_pref' => 'Also send me notifications by e-mail',
    'kinds' => [
        'booking_requested' => ['title' => 'New booking request', 'body' => ':name asked to book ":experience" on :date. Please answer within 12 hours.'],
        'booking_confirmed' => ['title' => 'Booking confirmed', 'body' => 'Your booking ":experience" on :date with :name is confirmed.'],
        'booking_declined' => ['title' => 'Booking declined', 'body' => ':name declined ":experience" on :date. Nothing was charged.'],
        'booking_expired' => ['title' => 'Request expired', 'body' => 'The request for ":experience" on :date was not answered in time and has expired.'],
        'booking_cancelled' => ['title' => 'Booking cancelled', 'body' => ':name cancelled ":experience" on :date.'],
        'payment_captured' => ['title' => 'Payment received', 'body' => 'We charged :amount TND for ":experience".'],
        'payment_released' => ['title' => 'Reservation released', 'body' => 'The :amount TND held for ":experience" was released.'],
        'payment_failed' => ['title' => 'Payment failed', 'body' => 'The payment for ":experience" did not go through.'],
        'refund_issued' => ['title' => 'Refund issued', 'body' => 'You are refunded :amount TND for ":experience".'],
        'message_received' => ['title' => 'New message', 'body' => ':name sent you a message about ":experience".'],
    ],
];
