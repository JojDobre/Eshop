<?php

namespace Modules\Invoices;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvoiceType: string implements HasColor, HasLabel
{
    case Proforma = 'proforma';
    case Invoice = 'invoice';
    case CreditNote = 'credit_note';

    public function getLabel(): string
    {
        return __('invoices::invoices.types.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Proforma => 'gray',
            self::Invoice => 'success',
            self::CreditNote => 'warning',
        };
    }
}
