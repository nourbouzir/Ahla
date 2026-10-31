<?php

return [
    'nav' => [
        'title' => 'Guide space', 'overview' => 'Overview', 'bookings' => 'Booking requests', 'calendar' => 'Calendar', 'clients' => 'Clients',
        'inbox' => 'Messages', 'experiences' => 'Experiences', 'availability' => 'Availability', 'earnings' => 'Earnings', 'verification' => 'Verification',
    ],
    'overview' => [
        'title' => 'Welcome back, :name', 'public_page' => 'View public page', 'new_experience' => 'New experience',
        'needs_answer' => 'Waiting for your answer', 'answer_in' => 'Answer within', 'expired' => 'expired', 'all_answered' => 'You are all caught up. New requests appear here.',
        'agenda' => 'Next 7 days', 'open_calendar' => 'Open calendar', 'today' => 'Today', 'nothing_scheduled' => 'Nothing scheduled this week.',
        'last_months' => 'Earnings, last 6 months', 'details' => 'Details', 'messages' => 'Latest messages', 'all' => 'See all', 'no_messages' => 'No conversations yet.',
    ],
    'banner' => [
        'no_profile' => 'Add your name, a short bio and your city so tourists can find you.', 'complete_profile' => 'Complete profile',
        'pending' => 'Your application is pending.', 'pending_text' => 'You will appear in search once an admin has verified you.',
        'rejected' => 'Your application was rejected.', 'rejected_text' => 'Read the reason and apply again.',
        'suspended' => 'Your account is suspended.', 'suspended_text' => 'You are hidden from search while it is suspended.',
        'go' => 'Open verification',
    ],
    'kpi' => [
        'pending' => 'Requests to answer', 'pending_hint' => 'Answer within 12 hours', 'pending_none' => 'None waiting',
        'upcoming' => 'Upcoming bookings', 'next' => 'Next: :date', 'upcoming_none' => 'Nothing booked yet',
        'earned' => 'Earned this month', 'clients' => 'Clients', 'completed' => ':n experiences completed',
    ],
    'checklist' => [
        'title' => 'Get ready for bookings', 'profile' => 'Complete your host profile', 'verified' => 'Get verified by our team',
        'experience' => 'Publish an experience', 'hours' => 'Set your weekly hours', 'photo' => 'Add a profile picture', 'done' => 'done',
    ],
    'clients' => [
        'title' => 'Clients', 'subtitle' => ':n people you have worked with or are about to', 'search' => 'Search', 'search_placeholder' => 'Name',
        'sort' => 'Sort by', 'sort_recent' => 'Most recent', 'sort_name' => 'Name', 'sort_spent' => 'Amount spent', 'sort_bookings' => 'Bookings',
        'apply' => 'Apply', 'stage' => 'Filter by stage', 'all' => 'All',
        'empty' => 'No clients yet', 'empty_text' => 'When a tourist books you, they show up here with their history and your private notes.',
        'no_match' => 'No client matches', 'no_match_text' => 'Try another name or clear the stage filter.',
        'col_client' => 'Client', 'col_stage' => 'Stage', 'col_bookings' => 'Bookings', 'col_spent' => 'Spent', 'col_last' => 'Last / next', 'open' => 'Open',
        'next_on' => 'Next: :date',
    ],
    'stage' => ['awaiting' => 'Awaiting your answer', 'upcoming' => 'Upcoming', 'returning' => 'Returning', 'past' => 'Past client', 'closed' => 'No completed visit'],
    'client' => [
        'back' => 'All clients', 'summary' => 'Client summary', 'message' => 'Message', 'completed' => 'Completed', 'history' => 'Booking history',
        'note' => 'Private note', 'note_hint' => 'Only you can see this. Great for allergies, interests or what to prepare.',
        'note_placeholder' => 'For example: vegetarian, loves photography, speaks a little Arabic.', 'note_saved' => 'Note saved.', 'contact' => 'Contact',
    ],
    'inbox' => [
        'title' => 'Messages', 'unread_count' => ':n unread', 'caught_up' => 'You are all caught up', 'show_all' => 'Show all', 'unread_only' => 'Unread only',
        'explain' => 'Each booking has its own private chat between you and the client. Messages are translated between English, French and Arabic.',
        'none_unread' => 'No unread messages', 'empty' => 'No conversations yet', 'empty_text' => 'A chat opens as soon as a tourist requests a booking.',
        'you' => 'You: ', 'no_messages' => 'No messages yet. Say hello!',
    ],
    'calendar' => [
        'title' => 'Calendar', 'prev' => 'Previous month', 'next' => 'Next month', 'today' => 'Today', 'day_off' => 'Day off', 'off' => 'Off',
        'hint' => 'Days off and weekly hours decide when tourists can book you.', 'edit_hours' => 'Edit availability',
    ],
    'earnings' => [
        'title' => 'Earnings', 'all_time' => 'All time', 'export' => 'Export CSV', 'period' => 'Period', 'gross' => 'Paid by clients', 'refunded' => 'Refunded', 'net' => 'You earn',
        'note' => 'Only payments that were really captured are counted, minus refunds. Ahla charges no commission in this version.',
        'empty' => 'No payments yet', 'empty_text' => 'Money appears here once a client has paid for a confirmed booking.', 'col_date' => 'Date', 'col_booking' => 'Booking',
    ],
];
