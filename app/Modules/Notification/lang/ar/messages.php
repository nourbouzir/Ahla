<?php

return [
    'title' => 'الإشعارات', 'read_all' => 'تعليم الكل كمقروء', 'new' => 'جديد', 'none' => 'لا توجد إشعارات بعد.',
    'open' => 'فتح', 'saved' => 'تم الحفظ.', 'email_pref' => 'أرسل لي الإشعارات أيضاً عبر البريد الإلكتروني',
    'kinds' => [
        'booking_requested' => ['title' => 'طلب حجز جديد', 'body' => 'طلب :name حجز «:experience» بتاريخ :date. يرجى الرد خلال 12 ساعة.'],
        'booking_confirmed' => ['title' => 'تم تأكيد الحجز', 'body' => 'تم تأكيد حجزك «:experience» بتاريخ :date مع :name.'],
        'booking_declined' => ['title' => 'تم رفض الحجز', 'body' => 'رفض :name «:experience» بتاريخ :date. لم يُخصم أي مبلغ.'],
        'booking_expired' => ['title' => 'انتهى الطلب', 'body' => 'لم يتم الرد على طلب «:experience» بتاريخ :date في الوقت المناسب وقد انتهت صلاحيته.'],
        'booking_cancelled' => ['title' => 'تم إلغاء الحجز', 'body' => 'ألغى :name «:experience» بتاريخ :date.'],
        'payment_captured' => ['title' => 'تم استلام الدفع', 'body' => 'تم خصم :amount دينار مقابل «:experience».'],
        'payment_released' => ['title' => 'تم الإفراج عن الحجز المالي', 'body' => 'تم الإفراج عن مبلغ :amount دينار المحجوز مقابل «:experience».'],
        'payment_failed' => ['title' => 'فشل الدفع', 'body' => 'لم تنجح عملية الدفع مقابل «:experience».'],
        'refund_issued' => ['title' => 'تم الاسترداد', 'body' => 'تم رد :amount دينار إليك مقابل «:experience».'],
        'message_received' => ['title' => 'رسالة جديدة', 'body' => 'أرسل لك :name رسالة بخصوص «:experience».'],
    ],
];
