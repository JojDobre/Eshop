<?php

namespace Modules\Returns;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ReturnStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Completed = 'completed';

    public function getLabel(): string
    {
        return __('returns::returns.statuses.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'info',
            self::InProgress => 'warning',
            self::Accepted, self::Completed => 'success',
            self::Rejected => 'danger',
        };
    }

    /** Je žiadosť vybavená (lehota už nebeží)? */
    public function isClosed(): bool
    {
        return in_array($this, [self::Rejected, self::Completed], true);
    }
}
