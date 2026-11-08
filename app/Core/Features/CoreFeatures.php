<?php

namespace App\Core\Features;

use App\Core\Content\ShopLocales;
use App\Core\Support\Currencies;
use App\Settings\CurrencySettings;
use App\Settings\GeneralSettings;
use RuntimeException;

/**
 * Prepínače funkcií jadra (jazyky, meny).
 */
final class CoreFeatures
{
    public static function register(FeatureToggles $toggles): void
    {
        $toggles->group('international', fn () => __('admin.features.groups.international'));

        $toggles->register(new FeatureToggle(
            key: 'core.locales',
            group: 'international',
            label: fn () => __('admin.features.locales'),
            description: fn () => __('admin.features.locales_help'),
            isEnabled: fn () => app(GeneralSettings::class)->locales_enabled && app(GeneralSettings::class)->locales !== [],
            set: function (bool $enabled): void {
                $settings = app(GeneralSettings::class);

                if ($enabled && $settings->locales === []) {
                    throw new RuntimeException(__('admin.features.locales_choose'));
                }

                $settings->locales_enabled = $enabled;
                $settings->save();
                app(ShopLocales::class)->refresh();
            },
            settingsUrl: fn () => url('/admin/settings/general'),
        ));

        $toggles->register(new FeatureToggle(
            key: 'core.currencies',
            group: 'international',
            label: fn () => __('admin.features.currencies'),
            description: fn () => __('admin.features.currencies_help'),
            isEnabled: fn () => app(CurrencySettings::class)->multicurrency_enabled && app(CurrencySettings::class)->enabled !== [],
            set: function (bool $enabled): void {
                $settings = app(CurrencySettings::class);

                if ($enabled && $settings->enabled === []) {
                    throw new RuntimeException(__('admin.features.currencies_choose'));
                }

                $settings->multicurrency_enabled = $enabled;
                $settings->save();
                app(Currencies::class)->refresh();
            },
            settingsUrl: fn () => url('/admin/settings/currencies'),
        ));

        $toggles->register(new FeatureToggle(
            key: 'core.ecb',
            group: 'international',
            label: fn () => __('admin.currencies.auto_update'),
            description: fn () => __('admin.currencies.auto_update_help'),
            isEnabled: fn () => app(CurrencySettings::class)->auto_update,
            set: function (bool $enabled): void {
                $settings = app(CurrencySettings::class);
                $settings->auto_update = $enabled;
                $settings->save();
            },
            settingsUrl: fn () => url('/admin/settings/currencies'),
        ));

        $toggles->group('appearance', fn () => __('admin.features.groups.appearance'));
        $toggles->register(new FeatureToggle(
            key: 'core.theme_editor',
            group: 'appearance',
            label: fn () => __('admin.theme_editor.feature'),
            description: fn () => __('admin.theme_editor.feature_help'),
            isEnabled: fn () => config('eshop.theme_editor') && app(GeneralSettings::class)->theme_editor_enabled,
            set: function (bool $enabled): void {
                if ($enabled && ! config('eshop.theme_editor')) {
                    throw new RuntimeException(__('admin.theme_editor.disabled_by_config'));
                }

                $settings = app(GeneralSettings::class);
                $settings->theme_editor_enabled = $enabled;
                $settings->save();
            },
        ));
    }
}
