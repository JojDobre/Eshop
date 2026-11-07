<?php

namespace Modules\Orders;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Processing = 'processing';
    case ReadyForPickup = 'ready_for_pickup';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return __('orders::admin.statuses.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Processing => 'warning',
            self::ReadyForPickup, self::Shipped => 'primary',
            self::Completed => 'success',
            self::Cancelled => 'gray',
        };
    }
}
