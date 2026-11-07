<?php

return [
    'proforma' => [
        'label' => 'Zálohová faktura k objednávce',
        'subject' => 'Zálohová faktura č. {document_number}',
        'body' => "Dobrý den {customer_name},\n\nv příloze posíláme zálohovou fakturu k objednávce č. **{order_number}**.\n\n[Zobrazit objednávku]({order_url})\n\nS pozdravem\\\n{shop_name}",
    ],
    'invoice' => [
        'label' => 'Faktura po zaplacení',
        'subject' => 'Faktura č. {document_number}',
        'body' => "Dobrý den {customer_name},\n\nděkujeme za platbu. V příloze posíláme fakturu k objednávce č. **{order_number}**.\n\n[Zobrazit objednávku]({order_url})\n\nS pozdravem\\\n{shop_name}",
    ],
    'credit_note' => [
        'label' => 'Dobropis',
        'subject' => 'Dobropis č. {document_number}',
        'body' => "Dobrý den {customer_name},\n\nv příloze posíláme dobropis k objednávce č. **{order_number}**.\n\n[Zobrazit objednávku]({order_url})\n\nS pozdravem\\\n{shop_name}",
    ],
];
