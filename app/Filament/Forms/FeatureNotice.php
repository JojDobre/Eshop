<?php

namespace App\Filament\Forms;

use App\Core\Features\FeatureToggle;
use App\Core\Features\FeatureToggles;
use App\Filament\Pages\Settings\ManageFeatures;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Group;

/**
 * Funkcia sa zapína a vypína na jednom mieste – v Nastavenia → Funkcie obchodu. Na stránke s jej nastaveniami
 * sa namiesto druhého prepínača zobrazí upozornenie, keď je vypnutá. Skryté pole drží hodnotu, aby od nej
 * mohli závisieť ďalšie polia formulára.
 */
final class FeatureNotice
{
    /**
     * @param  string  $feature  Kľúč funkcie, napr. „reviews“.
     * @param  string|null  $field  Pole nastavení, ktoré funkciu zapína (zostane ako skryté pole).
     */
    public static function make(string $feature, ?string $field = null): Group
    {
        $toggle = fn (): ?FeatureToggle => app(FeatureToggles::class)->find($feature);

        return Group::make(array_values(array_filter([
            $field !== null ? Hidden::make($field) : null,
            Callout::make(fn () => __('admin.feature_notice.off', ['feature' => ($toggle()?->label)() ?? '']))
                ->description(__('admin.feature_notice.help'))
                ->warning()
                ->actions([
                    Action::make('openFeatures_'.str_replace('.', '_', $feature))
                        ->label(__('admin.feature_notice.open'))
                        ->url(fn () => ManageFeatures::getUrl())
                        ->button()
                        ->size('sm'),
                ])
                ->visible(fn () => ($item = $toggle()) !== null && ! ($item->isEnabled)()),
        ])))->columnSpanFull();
    }
}
