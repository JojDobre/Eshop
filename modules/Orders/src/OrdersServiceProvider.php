<?php

namespace Modules\Orders;

use App\Core\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Modules\Orders\Events\OrderPlaced;
use Modules\Orders\Listeners\SendOrderEmails;
use Modules\Shipping\Models\ShippingMethod;

class OrdersServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'orders';
    }

    public function boot(): void
    {
        parent::boot();

        Event::listen(OrderPlaced::class, SendOrderEmails::class);

        // Košík: koľko chýba do dopravy zadarmo (najnižšia hranica zo spôsobov doručenia).
        View::composer('theme::templates.cart', function (\Illuminate\View\View $view): void {
            $threshold = ShippingMethod::query()->active()->whereNotNull('free_from')->where('price', '>', 0)->min('free_from');
            $view->with('freeShippingFrom', $threshold === null ? null : (int) $threshold);
        });
    }
}
