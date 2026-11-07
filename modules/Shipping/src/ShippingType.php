<?php

namespace Modules\Shipping;

use Filament\Support\Contracts\HasLabel;

enum ShippingType: string implements HasLabel
{
    /** Osobný odber na predajni. */
    case Pickup = 'pickup';

    /** Doručenie na adresu zákazníka. */
    case Delivery = 'delivery';

    public function getLabel(): string
    {
        return __('shipping::admin.types.'.$this->value);
    }

    public function requiresShippingAddress(): bool
    {
        return $this === self::Delivery;
    }
}
