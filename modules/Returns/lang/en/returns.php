<?php

return [
    'types' => [
        'withdrawal' => 'Contract withdrawal',
        'complaint' => 'Complaint',
    ],
    'statuses' => [
        'new' => 'New',
        'in_progress' => 'In progress',
        'accepted' => 'Accepted',
        'rejected' => 'Rejected',
        'completed' => 'Completed',
    ],
    'resolutions' => [
        'repair' => 'Repair',
        'replacement' => 'Replacement',
        'refund' => 'Refund',
        'discount' => 'Price discount',
    ],
    'no_items' => 'Select at least one item.',
    'submitted' => 'We have received your :type no. :number. A confirmation has been sent to :email.',
    'lookup_title' => 'Returns and complaints',
    'lookup_intro' => 'Enter the order number and the e-mail you used for it. The form works without a customer account.',
    'lookup_number' => 'Order number',
    'lookup_email' => 'Order e-mail',
    'lookup_submit' => 'Continue',
    'lookup_not_found' => 'We could not find an order with this number and e-mail.',
    'adr' => 'If you are not satisfied with the outcome, you can contact an alternative dispute resolution body – in Slovakia the Slovak Trade Inspection (www.soi.sk), in the Czech Republic the Czech Trade Inspection (adr.coi.cz).',
];
