<?php

namespace Modules\Shipping\Carriers;

final class TrackingResult
{
    public function __construct(
        public readonly ShipmentStatus $status,
        public readonly ?string $text = null,
    ) {}
}
