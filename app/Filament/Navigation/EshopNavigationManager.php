<?php

namespace App\Filament\Navigation;

use App\Settings\GeneralSettings;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationManager;
use Throwable;

/**
 * Menu administrácie bez položiek, ktoré si obchodník skryl (Nastavenia → Obchod → Menu administrácie).
 * Skrytá položka je stále dostupná cez priamu adresu – nejde o oprávnenie, iba o prehľadnosť.
 */
class EshopNavigationManager extends NavigationManager
{
    /** Položky, ktoré sa nedajú skryť (inak by sa nedalo vrátiť späť). */
    public const ALWAYS_VISIBLE = ['', 'settings/admin-menu'];

    /**
     * @return array<NavigationItem>
     */
    public function getNavigationItems(): array
    {
        $hidden = self::hidden();

        if ($hidden === []) {
            return parent::getNavigationItems();
        }

        return array_values(array_filter(parent::getNavigationItems(), fn (NavigationItem $item): bool => ! in_array(self::key($item), $hidden, true)));
    }

    /**
     * Všetky položky (aj skryté) – pre stránku s nastavením menu.
     *
     * @return array<NavigationItem>
     */
    public function getAllNavigationItems(): array
    {
        // Položky sa registrujú až pri prvom zostavení menu (cez panel, ktorý ich odovzdá tomuto správcovi).
        if (! $this->isNavigationMounted) {
            filament()->getCurrentOrDefaultPanel()->getNavigation();
        }

        return parent::getNavigationItems();
    }

    /** Kľúč položky: cesta v administrácii bez „/admin/“. */
    public static function key(NavigationItem $item): string
    {
        $path = trim((string) parse_url((string) $item->getUrl(), PHP_URL_PATH), '/');
        $admin = trim((string) parse_url(url(filament()->getCurrentOrDefaultPanel()->getPath()), PHP_URL_PATH), '/');

        return trim($admin !== '' && str_starts_with($path, $admin) ? substr($path, strlen($admin)) : $path, '/');
    }

    /**
     * @return list<string>
     */
    public static function hidden(): array
    {
        try {
            return array_values(array_diff((array) app(GeneralSettings::class)->admin_hidden_navigation, self::ALWAYS_VISIBLE));
        } catch (Throwable) {
            return [];
        }
    }
}
