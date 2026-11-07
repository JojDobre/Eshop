<?php

namespace Modules\Orders;

use Filament\Support\Contracts\HasLabel;

enum CouponType: string implements HasLabel
{
    case Percent = 'percent';
    case Fixed = 'fixed';
    case FreeShipping = 'free_shipping';

    public function getLabel(): string
    {
        return __('orders::admin.coupons.types.'.$this->value);
    }
}
