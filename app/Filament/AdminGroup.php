<?php

namespace App\Filament;

/**
 * Skupiny v menu administrácie (poradie je dané poradím v AdminPanelProvider).
 */
enum AdminGroup
{
    case Catalog;
    case Sales;
    case Content;
    case Settings;
    case System;

    public function label(): string
    {
        return __('admin.navigation.'.strtolower($this->name));
    }
}
