<?php

namespace Modules\Shipping\Carriers;

/**
 * Zaregistrovaní kuriéri (moduly ich pridávajú v Service Provideri).
 */
class CarrierRegistry
{
    /** @var array<string, ShippingCarrier> */
    private array $carriers = [];

    public function register(ShippingCarrier $carrier): void
    {
        $this->carriers[$carrier->key()] = $carrier;
    }

    public function find(?string $key): ?ShippingCarrier
    {
        return $key === null ? null : ($this->carriers[$key] ?? null);
    }

    /**
     * @return array<string, ShippingCarrier>
     */
    public function all(): array
    {
        return $this->carriers;
    }
}
