<?php

namespace Modules\Orders;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasColor, HasLabel
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';

    public function getLabel(): string
    {
        return __('orders::admin.payment_statuses.'.$this->value);
    }

    public function getColor(): string
    {
        return $this === self::Paid ? 'success' : 'danger';
    }
}
