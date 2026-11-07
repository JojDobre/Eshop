<?php

return [
    'proforma' => [
        'label' => 'Zálohová faktúra k objednávke',
        'subject' => 'Zálohová faktúra č. {document_number}',
        'body' => "Dobrý deň {customer_name},\n\nv prílohe posielame zálohovú faktúru k objednávke č. **{order_number}**.\n\n[Zobraziť objednávku]({order_url})\n\nS pozdravom\\\n{shop_name}",
    ],
    'invoice' => [
        'label' => 'Faktúra po zaplatení',
        'subject' => 'Faktúra č. {document_number}',
        'body' => "Dobrý deň {customer_name},\n\nďakujeme za platbu. V prílohe posielame faktúru k objednávke č. **{order_number}**.\n\n[Zobraziť objednávku]({order_url})\n\nS pozdravom\\\n{shop_name}",
    ],
    'credit_note' => [
        'label' => 'Dobropis',
        'subject' => 'Dobropis č. {document_number}',
        'body' => "Dobrý deň {customer_name},\n\nv prílohe posielame dobropis k objednávke č. **{order_number}**.\n\n[Zobraziť objednávku]({order_url})\n\nS pozdravom\\\n{shop_name}",
    ],
];
