<?php

namespace Modules\Stripe;

/**
 * Volania Stripe API, ktoré obchod používa (v testoch sa nahradí falošnou implementáciou).
 */
interface StripeApi
{
    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed> Relácia Checkout (id, url, …).
     */
    public function createCheckoutSession(array $params): array;

    /**
     * @return array<string, mixed>
     */
    public function retrieveCheckoutSession(string $id): array;

    /**
     * Overenie kľúča (vráti napr. „test“ alebo „live“).
     */
    public function verify(): string;
}
