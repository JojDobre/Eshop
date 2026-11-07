<?php

return [
    'proforma' => [
        'label' => 'Proforma invoice for an order',
        'subject' => 'Proforma invoice no. {document_number}',
        'body' => "Hello {customer_name},\n\nplease find attached the proforma invoice for order no. **{order_number}**.\n\n[View order]({order_url})\n\nKind regards\\\n{shop_name}",
    ],
    'invoice' => [
        'label' => 'Invoice after payment',
        'subject' => 'Invoice no. {document_number}',
        'body' => "Hello {customer_name},\n\nthank you for your payment. Please find attached the invoice for order no. **{order_number}**.\n\n[View order]({order_url})\n\nKind regards\\\n{shop_name}",
    ],
    'credit_note' => [
        'label' => 'Credit note',
        'subject' => 'Credit note no. {document_number}',
        'body' => "Hello {customer_name},\n\nplease find attached the credit note for order no. **{order_number}**.\n\n[View order]({order_url})\n\nKind regards\\\n{shop_name}",
    ],
];
