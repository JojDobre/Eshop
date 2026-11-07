<?php

namespace App\Filament\Pages\Settings;

use App\Filament\AdminGroup;
use Filament\Pages\SettingsPage;
use UnitEnum;

/**
 * Spoločný základ stránok nastavení: skupina v menu a oprávnenie.
 */
abstract class BaseSettingsPage extends SettingsPage
{
    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Settings;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }
}
