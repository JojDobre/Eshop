<?php

namespace Modules\Shipping\Carriers;

final class ShipmentResult
{
    /**
     * @param  string  $reference  identifikátor zásielky u kuriéra (na štítky a sledovanie)
     * @param  string|null  $label  PDF štítka, ak ho kuriér vrátil hneď
     */
    public function __construct(
        public readonly string $reference,
        public readonly ?string $trackingNumber = null,
        public readonly ?string $label = null,
    ) {}
}
