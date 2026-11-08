<?php

namespace Modules\Orders\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\Shipment;
use Modules\Orders\OrderStatus;
use Modules\Orders\PaymentStatus;
use Modules\Orders\Shipping\CarrierHealth;
use Modules\Payments\PaymentType;
use Modules\Shipping\Carriers\CarrierException;
use Modules\Shipping\Carriers\ShipmentRequest;
use Modules\Shipping\Carriers\ShipmentStatus;
use Throwable;

/**
 * Vytvorí zásielku objednávky u kuriéra (štítok, sledovacie číslo) a označí objednávku ako odoslanú.
 */
class CreateShipment
{
    /** Disk a priečinok so štítkami (súkromné, nie sú dostupné z webu). */
    public const DISK = 'local';

    public function __construct(private readonly CarrierHealth $health, private readonly ChangeOrderStatus $status) {}

    /**
     * @throws CarrierException
     */
    public function handle(Order $order, ?string $trackingNumber = null, ?int $weightGrams = null, ?User $user = null, bool $notifyCustomer = true): Shipment
    {
        $method = $order->shippingMethod;
        $carrier = $method?->carrierService();

        if ($carrier === null) {
            throw new CarrierException(__('orders::admin.shipments.no_carrier'), CarrierException::CONFIGURATION);
        }

        if (! $carrier->isConfigured()) {
            throw new CarrierException(__('orders::admin.shipments.not_configured', ['carrier' => $carrier->label()]), CarrierException::CONFIGURATION);
        }

        try {
            $result = $carrier->createShipment($this->request($order, $trackingNumber, $weightGrams));
            $this->health->success($carrier->key());
        } catch (Throwable $e) {
            $this->health->failure($carrier->key(), $e);

            throw $e instanceof CarrierException ? $e : new CarrierException($e->getMessage(), CarrierException::UNAVAILABLE, $e);
        }

        $cod = $this->codAmount($order);
        $shipment = $order->shipments()->create([
            'carrier' => $carrier->key(),
            'carrier_reference' => $result->reference,
            'tracking_number' => $result->trackingNumber,
            'status' => ShipmentStatus::Created,
            'cod_amount' => $cod,
            'cod_currency' => $cod !== null ? $order->currency : null,
        ]);

        if ($result->label !== null) {
            $path = 'labels/'.$order->number.'-'.$shipment->id.'.pdf';
            Storage::disk(self::DISK)->put($path, $result->label);
            $shipment->update(['label_path' => $path]);
        }

        // Odoslaná objednávka: zákazník dostane e-mail so sledovaním zásielky.
        if (in_array($order->status, [OrderStatus::New, OrderStatus::Processing], true)) {
            $this->status->handle($order->fresh(), OrderStatus::Shipped, $user, $notifyCustomer);
        }

        return $shipment;
    }

    private function request(Order $order, ?string $trackingNumber, ?int $weightGrams): ShipmentRequest
    {
        $name = trim((string) ($order->shipping_name ?: $order->billing_name));
        $parts = preg_split('/\s+/', $name, 2) ?: [$name];

        return new ShipmentRequest(
            reference: $order->number,
            firstName: $parts[0],
            lastName: $parts[1] ?? $parts[0],
            company: $order->billing_company,
            email: $order->email,
            phone: (string) $order->phone,
            street: $order->shipping_street ?: $order->billing_street,
            city: $order->shipping_city ?: $order->billing_city,
            zip: $order->shipping_zip ?: $order->billing_zip,
            country: (string) ($order->shipping_country ?: $order->billing_country),
            pickupPoint: $order->pickup_point,
            codAmount: $this->codAmount($order),
            currency: $order->currency,
            value: $order->total,
            weightGrams: $weightGrams ?? $this->weight($order),
            methodOptions: $order->shippingMethod?->carrier_options ?? [],
            note: null,
            trackingNumber: $trackingNumber,
        );
    }

    /** Dobierka: nezaplatená objednávka s platbou na dobierku. */
    private function codAmount(Order $order): ?int
    {
        return $order->payment_type === PaymentType::CashOnDelivery && $order->payment_status !== PaymentStatus::Paid ? $order->total : null;
    }

    /** Hmotnosť z produktov (gramy), najmenej 100 g. */
    private function weight(Order $order): int
    {
        $order->loadMissing('items.product');
        $grams = $order->items->sum(fn ($item) => (int) ($item->product?->weight ?? 0) * $item->quantity);

        return max(100, (int) $grams);
    }
}
