<?php

namespace Modules\Returns;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ReturnType: string implements HasColor, HasLabel
{
    /** Odstúpenie od zmluvy (vrátenie tovaru bez udania dôvodu). */
    case Withdrawal = 'withdrawal';

    /** Reklamácia (vada tovaru). */
    case Complaint = 'complaint';

    public function getLabel(): string
    {
        return __('returns::returns.types.'.$this->value);
    }

    public function getColor(): string
    {
        return $this === self::Withdrawal ? 'info' : 'warning';
    }

    /**
     * Zákonná lehota v dňoch: vrátenie peňazí pri odstúpení (14 dní), vybavenie reklamácie (30 dní).
     */
    public function deadlineDays(): int
    {
        return $this === self::Withdrawal ? 14 : 30;
    }
}
