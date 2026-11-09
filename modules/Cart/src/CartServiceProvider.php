<?php

namespace Modules\Cart;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Privacy\DataRetention;
use App\Core\Privacy\RetentionPolicy;
use App\Core\Support\CurrencyContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Modules\Cart\Models\Cart;

class CartServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'cart';
    }

    public function register(): void
    {
        $this->app->scoped(CartManager::class, fn ($app) => new CartManager(
            $app->make('session.store'),
            $app->make(CurrencyContext::class),
        ));
    }

    public function boot(): void
    {
        parent::boot();

        // GDPR: staré košíky (aj s e-mailom z opusteného košíka) sa mažú.
        $this->callAfterResolving(DataRetention::class, fn (DataRetention $retention) => $retention->register(new RetentionPolicy(
            'carts', fn () => __('admin.privacy.policies.carts'), fn () => __('admin.privacy.policies.carts_help'), 90, true,
            fn (Carbon $before) => Cart::query()->where('updated_at', '<', $before)->delete(),
            minDays: 7,
        )));

        // Počet kusov v košíku v hlavičke obchodu.
        View::composer('theme::partials.header', function (\Illuminate\View\View $view): void {
            $view->with('cartCount', app(CartManager::class)->itemCount());
        });
    }
}
