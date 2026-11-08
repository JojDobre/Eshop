<?php

namespace Modules\Marketing;

use App\Core\Features\FeatureToggle;
use App\Core\Features\FeatureToggles;
use App\Core\Installer\Installation;
use App\Core\Mail\EmailTemplateDefinition;
use App\Core\Mail\EmailTemplates;
use App\Core\Modules\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Modules\Marketing\Listeners\SubscribeFromCheckout;
use Modules\Marketing\Newsletter\Newsletter;
use Modules\Marketing\Tracking\TrackingCodes;
use Modules\Orders\Events\OrderPlaced;
use Modules\Orders\Models\Order;
use Modules\Storefront\Cookies\CookieConsent;

class MarketingServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'marketing';
    }

    public function register(): void
    {
        parent::register();
        $this->app->scoped(TrackingCodes::class);
    }

    public function boot(): void
    {
        parent::boot();

        Event::listen(OrderPlaced::class, SubscribeFromCheckout::class);
        $this->registerFeatures();

        // Pätička a pokladňa: formulár newslettera, súhlas v pokladni, e-mail pre opustený košík.
        View::composer('theme::*', fn (\Illuminate\View\View $view) => $view->with([
            'newsletterEnabled' => Newsletter::enabled(),
        ]));
        View::composer('theme::templates.checkout', fn (\Illuminate\View\View $view) => $view->with([
            'newsletterCheckout' => Newsletter::enabled() && MarketingSettings::get('newsletter_checkout'),
            'abandonedCartEmailUrl' => AbandonedCarts::enabled() ? route('marketing.cart.email') : null,
        ]));

        // Konverzia: stránka objednávky hneď po jej dokončení.
        View::composer('theme::templates.order', function (\Illuminate\View\View $view): void {
            $order = $view->getData()['order'] ?? null;

            if ($order instanceof Order && session('order_placed')) {
                app(TrackingCodes::class)->setPurchase($order->loadMissing('items'));
            }
        });

        CookieConsent::addCode('marketing.analytics', CookieConsent::ANALYTICS, fn () => app(TrackingCodes::class)->analytics());
        CookieConsent::addCode('marketing.ads', CookieConsent::MARKETING, fn () => app(TrackingCodes::class)->marketing());

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->call(fn () => app(AbandonedCarts::class)->send())
                ->name('marketing:abandoned-carts')
                ->everyThirtyMinutes()
                ->withoutOverlapping(20)
                ->when(fn () => app(Installation::class)->isInstalled());
        });

        $templates = $this->app->make(EmailTemplates::class);
        $templates->register(new EmailTemplateDefinition('marketing.newsletter_confirm', 'marketing::mail_templates.newsletter_confirm',
            ['confirm_url', 'shop_name'], sample: ['confirm_url' => url('/newsletter/potvrdit/x')]));
        $templates->register(new EmailTemplateDefinition('marketing.abandoned_cart', 'marketing::mail_templates.abandoned_cart',
            ['cart_items', 'cart_url', 'shop_name'], canBeDisabled: true, sample: ['cart_items' => "- Káva × 2\n- Hrnček × 1", 'cart_url' => url('/kosik')]));
    }

    private function registerFeatures(): void
    {
        $toggles = $this->app->make(FeatureToggles::class);
        $toggles->group('marketing', fn () => __('marketing::admin.features.group'));

        foreach (['newsletter' => 'newsletter_enabled', 'abandoned' => 'abandoned_enabled', 'feeds' => 'feeds_enabled'] as $feature => $setting) {
            $toggles->register(new FeatureToggle(
                key: 'marketing.'.$feature,
                group: 'marketing',
                label: fn () => __('marketing::admin.features.'.$feature),
                description: fn () => __('marketing::admin.features.'.$feature.'_help'),
                isEnabled: fn () => (bool) MarketingSettings::get($setting),
                set: function (bool $enabled) use ($setting): void {
                    $settings = app(MarketingSettings::class);
                    $settings->{$setting} = $enabled;
                    $settings->save();
                },
                settingsUrl: fn () => url('/admin/settings/marketing'),
                sizes: ['small' => false, 'medium' => true, 'large' => true],
            ));
        }
    }
}
