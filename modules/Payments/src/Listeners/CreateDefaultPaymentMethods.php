<?php

namespace Modules\Payments\Listeners;

use App\Core\Events\EshopInstalled;
use App\Core\Support\Price;
use Modules\Payments\Models\PaymentMethod;
use Modules\Payments\PaymentType;
use Modules\Shipping\Models\ShippingMethod;
use Modules\Shipping\ShippingType;

/**
 * Predvolené spôsoby platby po inštalácii.
 * Spúšťa sa po module Doprava (poradie modulov podľa závislostí), takže doprava už existuje.
 */
class CreateDefaultPaymentMethods
{
    public function handle(EshopInstalled $event): void
    {
        if (PaymentMethod::query()->exists()) {
            return;
        }

        $locale = in_array($event->locale, ['sk', 'cs', 'en'], true) ? $event->locale : 'en';
        $local = fn (array $texts) => [$event->locale => $texts[$locale]];
        $codFee = ['EUR' => '1.50', 'CZK' => '39', 'USD' => '2', 'GBP' => '1.50'][$event->currency] ?? '1.50';

        $pickup = ShippingMethod::query()->where('type', ShippingType::Pickup)->pluck('id');
        $delivery = ShippingMethod::query()->where('type', ShippingType::Delivery)->pluck('id');

        PaymentMethod::query()->create([
            'name' => $local(['sk' => 'Bankový prevod', 'cs' => 'Bankovní převod', 'en' => 'Bank transfer']),
            'description' => $local(['sk' => 'Platobné údaje dostanete po odoslaní objednávky.', 'cs' => 'Platební údaje obdržíte po odeslání objednávky.', 'en' => 'Payment details are shown after you place the order.']),
            'type' => PaymentType::BankTransfer,
            'currency' => $event->currency,
            'position' => 1,
        ]);

        PaymentMethod::query()->create([
            'name' => $local(['sk' => 'Dobierka', 'cs' => 'Dobírka', 'en' => 'Cash on delivery']),
            'description' => $local(['sk' => 'Platba kuriérovi pri prevzatí zásielky.', 'cs' => 'Platba kurýrovi při převzetí zásilky.', 'en' => 'Pay the courier when the parcel arrives.']),
            'type' => PaymentType::CashOnDelivery,
            'fee' => Price::toMinor($codFee, $event->currency),
            'currency' => $event->currency,
            'position' => 2,
        ])->shippingMethods()->sync($delivery);

        PaymentMethod::query()->create([
            'name' => $local(['sk' => 'Platba na predajni', 'cs' => 'Platba na prodejně', 'en' => 'Pay at the store']),
            'description' => $local(['sk' => 'Zaplatíte pri vyzdvihnutí objednávky.', 'cs' => 'Zaplatíte při vyzvednutí objednávky.', 'en' => 'Pay when you collect your order.']),
            'type' => PaymentType::Cash,
            'currency' => $event->currency,
            'position' => 3,
        ])->shippingMethods()->sync($pickup);

        // Zapne sa po nastavení platobnej brány (napr. Stripe).
        PaymentMethod::query()->create([
            'name' => $local(['sk' => 'Platba kartou', 'cs' => 'Platba kartou', 'en' => 'Card payment']),
            'description' => $local(['sk' => 'Bezpečná platba kartou, Apple Pay alebo Google Pay.', 'cs' => 'Bezpečná platba kartou, Apple Pay nebo Google Pay.', 'en' => 'Secure payment by card, Apple Pay or Google Pay.']),
            'type' => PaymentType::Card,
            'currency' => $event->currency,
            'position' => 0,
            'is_active' => false,
        ]);
    }
}
