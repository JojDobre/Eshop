<?php

namespace Modules\Returns;

use Filament\Support\Contracts\HasLabel;

/**
 * Výsledok vybavenia reklamácie alebo odstúpenia.
 */
enum ReturnOutcome: string implements HasLabel
{
    case Repaired = 'repaired';
    case Replaced = 'replaced';
    case Discount = 'discount';
    case Refunded = 'refunded';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return __('returns::admin.outcomes.'.$this->value);
    }
}
