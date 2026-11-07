<?php

namespace Modules\Shipping;

use Filament\Support\Contracts\HasLabel;

enum ShippingType: string implements HasLabel
{
    /** Osobný odber na predajni. */
    case Pickup = 'pickup';

    /** Doručenie na adresu zákazníka. */
    case Delivery = 'delivery';

    /** Bez dopravy (služby, digitálne produkty) – ponúkne sa iba košíku bez tovaru na doručenie. */
    case None = 'none';

    public function getLabel(): string
    {
        return __('shipping::admin.types.'.$this->value);
    }

    public function requiresShippingAddress(): bool
    {
        return $this === self::Delivery;
    }
}
