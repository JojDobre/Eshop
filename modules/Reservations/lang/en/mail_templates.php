<?php

return [
    'confirmation' => [
        'label' => 'Booking confirmation',
        'subject' => 'Booking {booking_number}: {booking_title}',
        'body' => "Hello {customer_name},\n\nthank you for your booking.\n\n**{booking_title}**\\\n{booking_date}\\\n{booking_resource}\n\n{amount_due}\n\nDetails, add to calendar and cancellation:\\\n{booking_url}\n\nKind regards\\\n{shop_name}",
    ],
    'reminder' => [
        'label' => 'Booking reminder',
        'subject' => 'Reminder: {booking_title} {booking_date}',
        'body' => "Hello {customer_name},\n\nthis is a reminder of your booking:\n\n**{booking_title}**\\\n{booking_date}\\\n{booking_resource}\n\nIf you cannot come, please cancel it: {booking_url}\n\nKind regards\\\n{shop_name}",
    ],
    'cancelled' => [
        'label' => 'Booking cancellation',
        'subject' => 'Booking {booking_number} has been cancelled',
        'body' => "Hello {customer_name},\n\nthe booking **{booking_title}** ({booking_date}) has been cancelled.\n\nKind regards\\\n{shop_name}",
    ],
    'notification' => [
        'label' => 'Shop booking notification',
        'subject' => 'Booking {booking_number}: {booking_status}',
        'body' => "Booking {booking_number} – {booking_status}\n\n**{booking_title}**\\\n{booking_date}\\\n{booking_resource}\n\nCustomer: {customer_name}\n\n{amount_due}",
    ],
];
