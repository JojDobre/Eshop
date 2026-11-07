<?php

return [
    'label' => 'request',
    'plural' => 'Returns and complaints',
    'number' => 'Number',
    'type' => 'Type',
    'status' => 'Status',
    'order' => 'Order',
    'customer' => 'Customer',
    'items' => 'Items',
    'reason' => 'Reason / defect description',
    'preferred_resolution' => 'Requested resolution',
    'refund_account' => 'Refund account',
    'resolution' => 'Resolution',
    'resolution_help' => 'The customer sees it in the e-mail and on the order page.',
    'created_at' => 'Received',
    'deadline_at' => 'Deadline',
    'resolved_at' => 'Resolved',
    'change_status' => 'Change status',
    'notify_customer' => 'Send the customer an e-mail',
    'status_changed' => 'The status has been changed.',
    'days_left' => '{0} deadline is today|{1} :count day left|[2,*] :count days left|[-1000000,-1] deadline has passed',
    'deadline_help' => [
        'withdrawal' => 'Refund within 14 days of receiving the withdrawal (you may wait until you get the goods back).',
        'complaint' => 'Resolve the complaint within 30 days of its submission.',
    ],
];
