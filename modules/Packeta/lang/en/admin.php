<?php

return [
    'section_keys' => 'Credentials',
    'section_keys_help' => 'Find them in the Packeta client section → My account → API. Packeta has no sandbox; fees apply only when a parcel is handed over.',
    'api_key' => 'API key',
    'api_key_help' => '16 characters, used for the pickup point map.',
    'api_password' => 'API password',
    'sender' => 'Sender label',
    'sender_help' => 'Sender (eshop) name from the client section, if you have more than one.',
    'label_format' => 'Label format',
    'country' => 'Pickup point country',
    'point_groups' => 'Offer',
    'groups' => ['points' => 'Pickup points', 'zbox' => 'Z-BOX lockers'],
    'carrier_id' => 'Carrier ID for home delivery',
    'carrier_id_help' => 'E.g. 131 = Packeta Home SK, 106 = Packeta Home CZ (carrier list in the client section). Home delivery only.',
    'missing_password' => 'Packeta: the API password is missing (Settings → Packeta).',
    'missing_carrier_id' => 'Packeta: the carrier ID for home delivery is missing in the shipping method settings.',
    'unavailable' => 'Packeta: the service is not responding, try again later.',
    'invalid_response' => 'Packeta: invalid server response.',
];
