<?php

namespace Modules\Returns;

use Filament\Support\Contracts\HasLabel;

enum ComplaintResolution: string implements HasLabel
{
    case Repair = 'repair';
    case Replacement = 'replacement';
    case Refund = 'refund';
    case Discount = 'discount';

    public function getLabel(): string
    {
        return __('returns::returns.resolutions.'.$this->value);
    }
}
