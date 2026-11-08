<?php

return [
    'section_keys' => 'WebShip API access',
    'section_keys_help' => 'The same user name and password as for the webship.sps-sro.sk portal. Your SPS sales rep must enable API access. The sender follows your SPS contract.',
    'username' => 'User name',
    'password' => 'Password',
    'section_shipments' => 'Shipments',
    'service' => 'Service',
    'service_help' => 'SPS service code from your contract, e.g. expres.',
    'notify' => 'Recipient notification',
    'notify_types' => [0 => 'None', 1 => 'E-mail', 2 => 'SMS', 3 => 'E-mail and SMS'],
    'insure' => 'Insure for the order value',
    'insure_help' => 'Insurance is charged according to the SPS price list.',
    'cod_iban' => 'IBAN for cash on delivery',
    'cod_iban_help' => 'Empty = IBAN from Settings → Company (or the account from your SPS contract).',
    'missing_credentials' => 'SPS: user name or password is missing (Settings → SPS).',
    'unavailable' => 'SPS: the service is not responding, try again later.',
    'invalid_response' => 'SPS: invalid server response.',
    'label_only_on_create' => 'SPS: the label is saved when the shipment is created. If it is missing, print it in the WebShip portal.',
];
