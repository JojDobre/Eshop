<?php

namespace Modules\Tax;

use App\Core\Events\EshopInstalled;
use App\Core\Features\FeatureToggle;
use App\Core\Features\FeatureToggles;
use App\Core\Modules\ModuleServiceProvider;
use App\Settings\TaxSettings;
use Illuminate\Support\Facades\Event;
use Modules\Tax\Listeners\CreateDefaultTaxRates;

class TaxServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'tax';
    }

    public function boot(): void
    {
        parent::boot();

        // Prepínače v Nastavenia → Funkcie obchodu.
        $toggles = $this->app->make(FeatureToggles::class);
        $toggles->group('tax', fn () => __('tax::admin.settings.section_eu'));
        foreach (['oss_enabled' => 'oss', 'reverse_charge_enabled' => 'reverse_charge'] as $setting => $label) {
            $toggles->register(new FeatureToggle(
                key: 'tax.'.$setting,
                group: 'tax',
                label: fn () => __('tax::admin.settings.'.$label),
                description: fn () => __('tax::admin.settings.'.$label.'_help'),
                isEnabled: fn () => (bool) app(TaxSettings::class)->{$setting},
                set: function (bool $enabled) use ($setting): void {
                    $settings = app(TaxSettings::class);
                    $settings->{$setting} = $enabled;
                    $settings->save();
                },
                settingsUrl: fn () => url('/admin/settings/tax'),
            ));
        }

        Event::listen(EshopInstalled::class, CreateDefaultTaxRates::class);
    }
}
