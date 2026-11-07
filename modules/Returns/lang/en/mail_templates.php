<?php

return [
    'received' => [
        'label' => 'Withdrawal or complaint received (customer)',
        'subject' => '{request_type} no. {request_number} – confirmation',
        'body' => 'Hello {customer_name},

we confirm receipt of your **{request_type}** no. **{request_number}** for order no. {order_number}.

Items: {request_items}

We will handle it by **{deadline}** at the latest. Please send the goods to the shop address unless agreed otherwise.

[View order]({order_url})

Kind regards\\
{shop_name}',
    ],
    'notification' => [
        'label' => 'New withdrawal or complaint (merchant)',
        'subject' => '{request_type} no. {request_number} for order {order_number}',
        'body' => '# {request_type} no. {request_number}

Order no. {order_number}, customer {customer_name}.

Items: {request_items}

{reason}

Deadline: **{deadline}**

[Open in administration]({admin_url})',
    ],
    'status' => [
        'label' => 'Withdrawal or complaint status change',
        'subject' => '{request_type} no. {request_number}: {request_status}',
        'body' => 'Hello {customer_name},

the status of your **{request_type}** no. {request_number} has changed to **{request_status}**.

{resolution}

[View order]({order_url})

Kind regards\\
{shop_name}',
    ],
];
