<?php

namespace Modules\Catalog;

use App\Core\Features\FeatureToggle;
use App\Core\Features\FeatureToggles;
use App\Settings\CatalogSettings;
use RuntimeException;
use Throwable;

/**
 * Funkcie katalógu, ktoré sa zapínajú v Nastavenia → Funkcie obchodu.
 */
final class CatalogFeatures
{
    public const ALL = ['product_types', 'extra_custom_fields', 'addons', 'bundles', 'warehouses', 'batches', 'expiration', 'preorders', 'digital'];

    /**
     * Predvoľby podľa veľkosti obchodu (PLAN.md, kapitola 4). Funkcia bez veľkosti je pri nej voliteľná.
     */
    private const SIZES = [
        'product_types' => ['medium' => true, 'large' => true],
        'extra_custom_fields' => ['large' => true],
        'addons' => ['medium' => true, 'large' => true],
        'bundles' => ['medium' => true, 'large' => true],
        'warehouses' => ['small' => false, 'medium' => false, 'large' => true],
        'preorders' => ['large' => true],
    ];

    /** Funkcie s ďalšími nastaveniami (odkaz z prehľadu funkcií). */
    private const WITH_SETTINGS = ['expiration', 'digital'];

    /** Závislosti medzi funkciami. */
    private const REQUIRES = ['expiration' => 'batches'];

    public static function enabled(string $feature): bool
    {
        try {
            return app(CatalogSettings::class)->enabled($feature);
        } catch (Throwable) {
            return false;
        }
    }

    public static function register(FeatureToggles $toggles): void
    {
        $toggles->group('catalog', fn () => __('catalog::admin.features.group'));

        foreach (self::ALL as $feature) {
            $toggles->register(new FeatureToggle(
                key: 'catalog.'.$feature,
                group: 'catalog',
                label: fn () => __('catalog::admin.features.'.$feature),
                description: fn () => __('catalog::admin.features.'.$feature.'_help'),
                isEnabled: fn () => self::enabled($feature),
                settingsUrl: in_array($feature, self::WITH_SETTINGS, true) ? fn () => url('/admin/settings/catalog') : null,
                sizes: self::SIZES[$feature] ?? [],
                set: function (bool $enabled) use ($feature): void {
                    if ($enabled && isset(self::REQUIRES[$feature]) && ! self::enabled(self::REQUIRES[$feature])) {
                        throw new RuntimeException(__('catalog::admin.features.requires', ['feature' => __('catalog::admin.features.'.self::REQUIRES[$feature])]));
                    }

                    $settings = app(CatalogSettings::class);
                    $settings->{'feature_'.$feature} = $enabled;

                    // Vypnutie funkcie vypne aj funkcie, ktoré ju potrebujú.
                    if (! $enabled) {
                        foreach (self::REQUIRES as $dependent => $required) {
                            if ($required === $feature) {
                                $settings->{'feature_'.$dependent} = false;
                            }
                        }
                    }

                    $settings->save();
                },
            ));
        }
    }
}
