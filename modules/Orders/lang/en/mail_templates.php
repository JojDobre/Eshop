<?php

return [
    'confirmation' => [
        'label' => 'Order confirmation (customer)',
        'subject' => 'Order confirmation no. {order_number}',
        'body' => <<<'MD'
# Thank you for your order

Hello {customer_name},

we have received your order no. **{order_number}**. We will keep you informed about the next steps.

{order_items}

{payment_details}

{shipping_details}

[View order]({order_url})

Kind regards\
{shop_name}
MD,
    ],
    'notification' => [
        'label' => 'New order (merchant)',
        'subject' => 'New order no. {order_number}',
        'body' => <<<'MD'
# New order no. {order_number}

**{customer_name}** ({customer_email}, {customer_phone}) – total **{order_total}**.

{order_items}

Shipping: {shipping_method}, payment: {payment_method}.

{customer_note}

[Open in administration]({admin_url})
MD,
    ],
    'status_processing' => [
        'label' => 'Order is being processed',
        'subject' => 'Order no. {order_number} is being processed',
        'body' => "Hello {customer_name},\n\nwe have started processing your order no. **{order_number}**.\n\n[View order]({order_url})\n\nKind regards\\\n{shop_name}",
    ],
    'status_ready_for_pickup' => [
        'label' => 'Order is ready for pickup',
        'subject' => 'Order no. {order_number} is ready for pickup',
        'body' => "Hello {customer_name},\n\nyour order no. **{order_number}** is ready for pickup.\n\n{shipping_details}\n\n[View order]({order_url})\n\nKind regards\\\n{shop_name}",
    ],
    'status_shipped' => [
        'label' => 'Order has been shipped',
        'subject' => 'Order no. {order_number} has been shipped',
        'body' => "Hello {customer_name},\n\nyour order no. **{order_number}** has been handed over for delivery.\n\n{shipping_details}\n\n[View order]({order_url})\n\nKind regards\\\n{shop_name}",
    ],
    'status_completed' => [
        'label' => 'Order is completed',
        'subject' => 'Order no. {order_number} is completed',
        'body' => "Hello {customer_name},\n\nyour order no. **{order_number}** is completed. Thank you for shopping with us.\n\nKind regards\\\n{shop_name}",
    ],
    'status_cancelled' => [
        'label' => 'Order has been cancelled',
        'subject' => 'Order no. {order_number} has been cancelled',
        'body' => "Hello {customer_name},\n\nyour order no. **{order_number}** has been cancelled. If you have any questions, reply to this e-mail.\n\nKind regards\\\n{shop_name}",
    ],
];
