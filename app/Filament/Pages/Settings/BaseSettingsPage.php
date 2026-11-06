<?php

namespace App\Filament\Pages\Settings;

use Filament\Pages\SettingsPage;

/**
 * Spoločný základ stránok nastavení: skupina v menu a oprávnenie.
 */
abstract class BaseSettingsPage extends SettingsPage
{
    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.settings');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }
}
