<?php

namespace Modules\Shipping\Carriers;

use Filament\Forms\Components\CheckboxList;
use Modules\Shipping\Models\PickupPoint;
use Modules\Shipping\Models\ShippingMethod;
use Modules\Shipping\ShippingType;

/**
 * Vlastné výdajné miesta obchodu (predajne, partnerské výdajné miesta…), ktoré obchodník spravuje
 * v administrácii. Zásielky sú ako pri vlastnom kuriérovi: bez štítkov, sledovacie číslo je nepovinné.
 */
class OwnPointsCarrier extends ManualCarrier
{
    public const KEY = 'own_points';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return __('shipping::admin.own_points.carrier');
    }

    public function supports(ShippingType $type): bool
    {
        return $type === ShippingType::PickupPoint;
    }

    public function pickupSelector(ShippingMethod $method): ?array
    {
        return ['list' => true];
    }

    public function methodFields(): array
    {
        return [
            CheckboxList::make('point_codes')->label(__('shipping::admin.own_points.offered'))
                ->helperText(__('shipping::admin.own_points.offered_help'))
                ->options(fn () => PickupPoint::query()->where('carrier', self::KEY)->orderBy('name')->get()
                    ->mapWithKeys(fn (PickupPoint $point) => [$point->code => $point->name.' – '.$point->address()])->all())
                ->columns(2)->columnSpanFull(),
        ];
    }

    public function trackingUrl(string $trackingNumber, array $methodOptions = []): ?string
    {
        return null;
    }

    /** Miesta sa spravujú ručne, nič sa nesťahuje. */
    public function syncPickupPoints(): int
    {
        return PickupPoint::query()->where('carrier', self::KEY)->where('is_active', true)->count();
    }
}
