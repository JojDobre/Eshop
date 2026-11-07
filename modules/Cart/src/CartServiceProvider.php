<?php

namespace Modules\Cart;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Support\CurrencyContext;
use Illuminate\Support\Facades\View;

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

        // Počet kusov v košíku v hlavičke obchodu.
        View::composer('theme::partials.header', function (\Illuminate\View\View $view): void {
            $view->with('cartCount', app(CartManager::class)->itemCount());
        });
    }
}
