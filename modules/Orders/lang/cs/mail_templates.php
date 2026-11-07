<?php

return [
    'confirmation' => [
        'label' => 'Potvrzení objednávky (zákazník)',
        'subject' => 'Potvrzení objednávky č. {order_number}',
        'body' => <<<'MD'
# Děkujeme za objednávku

Dobrý den {customer_name},

přijali jsme vaši objednávku č. **{order_number}**. O dalším postupu vás budeme informovat.

{order_items}

{payment_details}

{shipping_details}

[Zobrazit objednávku]({order_url})

S pozdravem\
{shop_name}
MD,
    ],
    'notification' => [
        'label' => 'Nová objednávka (obchodník)',
        'subject' => 'Nová objednávka č. {order_number}',
        'body' => <<<'MD'
# Nová objednávka č. {order_number}

**{customer_name}** ({customer_email}, {customer_phone}) – celkem **{order_total}**.

{order_items}

Doprava: {shipping_method}, platba: {payment_method}.

{customer_note}

[Otevřít v administraci]({admin_url})
MD,
    ],
    'status_processing' => [
        'label' => 'Objednávka se zpracovává',
        'subject' => 'Objednávka č. {order_number} se zpracovává',
        'body' => "Dobrý den {customer_name},\n\nvaši objednávku č. **{order_number}** jsme začali zpracovávat.\n\n[Zobrazit objednávku]({order_url})\n\nS pozdravem\\\n{shop_name}",
    ],
    'status_ready_for_pickup' => [
        'label' => 'Objednávka je připravena k vyzvednutí',
        'subject' => 'Objednávka č. {order_number} je připravena k vyzvednutí',
        'body' => "Dobrý den {customer_name},\n\nvaše objednávka č. **{order_number}** je připravena k vyzvednutí.\n\n{shipping_details}\n\n[Zobrazit objednávku]({order_url})\n\nS pozdravem\\\n{shop_name}",
    ],
    'status_shipped' => [
        'label' => 'Objednávka byla odeslána',
        'subject' => 'Objednávka č. {order_number} byla odeslána',
        'body' => "Dobrý den {customer_name},\n\nvaši objednávku č. **{order_number}** jsme předali k doručení.\n\n{shipping_details}\n\n[Zobrazit objednávku]({order_url})\n\nS pozdravem\\\n{shop_name}",
    ],
    'status_completed' => [
        'label' => 'Objednávka je vyřízena',
        'subject' => 'Objednávka č. {order_number} je vyřízena',
        'body' => "Dobrý den {customer_name},\n\nvaše objednávka č. **{order_number}** je vyřízena. Děkujeme za nákup.\n\nS pozdravem\\\n{shop_name}",
    ],
    'status_cancelled' => [
        'label' => 'Objednávka byla zrušena',
        'subject' => 'Objednávka č. {order_number} byla zrušena',
        'body' => "Dobrý den {customer_name},\n\nvaše objednávka č. **{order_number}** byla zrušena. Máte-li dotazy, odpovězte na tento e-mail.\n\nS pozdravem\\\n{shop_name}",
    ],
];
