<?php

return [
    'section_keys' => 'Balíkobot API access',
    'section_keys_help' => 'Find the API user and key in the Balíkobot client zone (a paid service; you keep your own carrier contracts). The sender is set up in Balíkobot.',
    'api_user' => 'API user',
    'api_key' => 'API key',
    'section_shipments' => 'Shipments',
    'auto_order' => 'Hand over data to the carrier immediately',
    'auto_order_help' => 'Off: you hand over shipments yourself in the Balíkobot client zone (order method).',
    'how_to' => 'Choose the carrier and service on the shipping method (Settings → Shipping). For pickup points the branch list is loaded per carrier and service.',
    'pickup_points' => 'Stored pickup points: :count. Updated daily.',
    'shipper' => 'Carrier',
    'service_type' => 'Service (service_type)',
    'service_type_help' => 'Carrier service code in Balíkobot, e.g. DPD 1 = Classic, 3 = Pickup. Empty for some carriers.',
    'missing_credentials' => 'Balíkobot: API user or key is missing (Settings → Balíkobot).',
    'missing_shipper' => 'Balíkobot: no carrier is selected on the shipping method.',
    'unauthorized' => 'Balíkobot: invalid API user or key.',
    'unavailable' => 'Balíkobot: the service is not responding, try again later.',
    'invalid_response' => 'Balíkobot: invalid server response.',
    'labels_one_shipper' => 'Balíkobot: labels can be printed together only for shipments of one carrier.',
];
