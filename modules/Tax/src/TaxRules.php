<?php

namespace Modules\Tax;

use App\Settings\GeneralSettings;
use App\Settings\TaxSettings;
use Modules\Tax\Models\TaxRate;
use Modules\Tax\Vies\ViesClient;

/**
 * Pravidlá DPH pre objednávku podľa krajiny doručenia a zákazníka.
 */
class TaxRules
{
    public function __construct(
        private readonly TaxSettings $settings,
        private readonly GeneralSettings $general,
        private readonly ViesClient $vies,
    ) {}

    /**
     * @param  string|null  $destination  Krajina doručenia tovaru (null = osobný odber v obchode).
     */
    public function context(?string $destination, bool $isCompany = false, ?string $vatId = null): TaxContext
    {
        $home = $this->general->country;

        if (! $this->settings->vat_payer || $destination === null || $destination === $home || ! EuVat::isMember($destination) || ! EuVat::isMember($home)) {
            return TaxContext::domestic($home);
        }

        if ($this->settings->reverse_charge_enabled && $isCompany && filled($vatId) && $this->vies->check($destination, (string) $vatId) === true) {
            return new TaxContext(TaxContext::REVERSE_CHARGE, $destination, $home);
        }

        if ($this->settings->oss_enabled) {
            return new TaxContext(TaxContext::OSS, $destination, $home, $this->settings->oss_keep_gross_prices);
        }

        return TaxContext::domestic($home);
    }

    /**
     * Sadzba DPH pre položku v danom kontexte.
     *
     * @param  float  $homeRate  Sadzba v krajine obchodu (už so zohľadnením neplatcu DPH).
     */
    public function rate(TaxContext $context, float $homeRate, ?TaxRate $class): float
    {
        return match ($context->mode) {
            TaxContext::REVERSE_CHARGE => 0.0,
            TaxContext::OSS => $class?->rateFor($context->country, $context->homeCountry) ?? $homeRate,
            default => $homeRate,
        };
    }

    /**
     * Cena s DPH po zmene sadzby: pri OSS s rovnakou konečnou cenou ostáva, inak sa prepočíta z ceny bez DPH.
     */
    public function gross(TaxContext $context, int $gross, float $homeRate, float $rate): int
    {
        if ($rate === $homeRate || ($context->mode === TaxContext::OSS && $context->keepGrossPrices)) {
            return $gross;
        }

        $net = $gross / (1 + $homeRate / 100);

        return (int) round($net * (1 + $rate / 100));
    }
}
