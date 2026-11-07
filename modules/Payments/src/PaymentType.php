<?php

namespace Modules\Payments;

use Filament\Support\Contracts\HasLabel;

enum PaymentType: string implements HasLabel
{
    /** Bankový prevod vopred. */
    case BankTransfer = 'bank_transfer';

    /** Platba kuriérovi pri prevzatí. */
    case CashOnDelivery = 'cash_on_delivery';

    /** Hotovosť (alebo karta na predajni) pri osobnom odbere. */
    case Cash = 'cash';

    /** Platba kartou online cez platobnú bránu (napr. Stripe). */
    case Card = 'card';

    public function getLabel(): string
    {
        return __('payments::admin.types.'.$this->value);
    }

    /**
     * Platí zákazník vopred (objednávka čaká na platbu)?
     */
    public function isPaidInAdvance(): bool
    {
        return $this === self::BankTransfer || $this === self::Card;
    }

    /**
     * Potrebuje platobnú bránu (bez nastavenej brány sa v pokladni nezobrazí)?
     */
    public function requiresGateway(): bool
    {
        return $this === self::Card;
    }
}
