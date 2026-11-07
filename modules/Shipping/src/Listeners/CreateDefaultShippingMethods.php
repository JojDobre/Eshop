<?php

namespace Modules\Shipping\Listeners;

use App\Core\Events\EshopInstalled;
use App\Core\Support\Price;
use Modules\Shipping\Models\ShippingMethod;
use Modules\Shipping\ShippingType;

/**
 * Predvolené spôsoby dopravy po inštalácii (obchodník ich upraví v administrácii).
 */
class CreateDefaultShippingMethods
{
    public function handle(EshopInstalled $event): void
    {
        if (ShippingMethod::query()->exists()) {
            return;
        }

        $locale = in_array($event->locale, ['sk', 'cs', 'en'], true) ? $event->locale : 'en';
        $local = fn (array $texts) => [$event->locale => $texts[$locale]];
        $courierPrice = ['EUR' => '4.90', 'CZK' => '119', 'USD' => '6.90', 'GBP' => '4.50'][$event->currency] ?? '5';
        $freeFrom = ['EUR' => '60', 'CZK' => '1500', 'USD' => '75', 'GBP' => '50'][$event->currency] ?? '60';

        ShippingMethod::query()->create([
            'name' => $local(['sk' => 'Osobný odber', 'cs' => 'Osobní odběr', 'en' => 'In-store pickup']),
            'description' => $local(['sk' => 'Vyzdvihnutie na našej predajni.', 'cs' => 'Vyzvednutí na naší prodejně.', 'en' => 'Collect your order at our store.']),
            'type' => ShippingType::Pickup,
            'price' => 0,
            'currency' => $event->currency,
            'position' => 1,
        ]);

        ShippingMethod::query()->create([
            'name' => $local(['sk' => 'Kuriér na adresu', 'cs' => 'Kurýr na adresu', 'en' => 'Courier delivery']),
            'description' => $local(['sk' => 'Doručenie do 1 – 3 pracovných dní.', 'cs' => 'Doručení do 1–3 pracovních dnů.', 'en' => 'Delivered within 1–3 business days.']),
            'type' => ShippingType::Delivery,
            'price' => Price::toMinor($courierPrice, $event->currency),
            'free_from' => Price::toMinor($freeFrom, $event->currency),
            'currency' => $event->currency,
            'position' => 2,
        ]);
    }
}
