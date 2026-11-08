<?php

namespace Modules\Reservations\Filament;

use App\Filament\AdminGroup;
use UnitEnum;

/**
 * Spoločné nastavenie ponuky rezervácií v administrácii.
 */
trait ReservationsNav
{
    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Sales;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('reservations.manage') ?? false;
    }
}
