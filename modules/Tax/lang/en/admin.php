<?php

return [
    'navigation' => 'VAT',
    'settings' => [
        'vat_payer' => 'I am registered for VAT',
        'vat_payer_help' => 'If you are not a VAT payer, prices are shown without a VAT breakdown and documents state “Not a VAT payer”.',
        'prices_include_vat' => 'I enter prices including VAT',
        'prices_include_vat_help' => 'When off, you enter product prices without VAT and the shop adds VAT according to the product rate.',
    ],
    'rates' => [
        'label' => 'VAT rate',
        'plural' => 'VAT rates',
        'name' => 'Name',
        'rate' => 'Rate',
        'position' => 'Order',
        'is_default' => 'Default',
        'is_default_help' => 'Used for products without a selected rate and for shipping and payment.',
    ],
];
