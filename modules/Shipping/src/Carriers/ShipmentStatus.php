<?php

namespace Modules\Shipping\Carriers;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Zjednotený stav zásielky (kuriéri prevádzajú svoje kódy na tieto stavy).
 */
enum ShipmentStatus: string implements HasColor, HasLabel
{
    case Created = 'created';
    case InTransit = 'in_transit';
    case ReadyForPickup = 'ready_for_pickup';
    case Delivered = 'delivered';
    case Returned = 'returned';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return __('shipping::admin.shipment_statuses.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Delivered => 'success',
            self::Returned, self::Cancelled => 'danger',
            self::ReadyForPickup => 'warning',
            default => 'info',
        };
    }

    /** Konečný stav – zásielka sa už ďalej nesleduje. */
    public function isFinal(): bool
    {
        return in_array($this, [self::Delivered, self::Returned, self::Cancelled], true);
    }
}
