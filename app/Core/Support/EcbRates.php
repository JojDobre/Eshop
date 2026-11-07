<?php

namespace App\Core\Support;

use App\Settings\CurrencySettings;
use App\Settings\GeneralSettings;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Denné kurzy Európskej centrálnej banky (zadarmo, bez registrácie).
 */
class EcbRates
{
    public const URL = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';

    /**
     * Stiahne kurzy a uloží ich pre zapnuté meny. Vráti uložené kurzy.
     *
     * @return array<string, string>
     */
    public function update(): array
    {
        $eur = $this->fetch();
        $default = app(GeneralSettings::class)->default_currency;
        $settings = app(CurrencySettings::class);

        if (! isset($eur[$default])) {
            throw new RuntimeException("ECB does not publish the rate for {$default}.");
        }

        $rates = $settings->rates;

        foreach ($settings->enabled as $currency) {
            if (isset($eur[$currency])) {
                // Kurzy ECB sú voči euru, prepočet na predvolenú menu obchodu.
                $rates[$currency] = (string) BigDecimal::of($eur[$currency])->dividedBy($eur[$default], 6, RoundingMode::HalfUp)->strippedOfTrailingZeros();
            }
        }

        $settings->rates = $rates;
        $settings->rates_updated_at = now()->toIso8601String();
        $settings->save();
        app(Currencies::class)->refresh();

        return $rates;
    }

    /**
     * @return array<string, string> mena => počet jednotiek za 1 EUR (vrátane EUR = 1)
     */
    public function fetch(): array
    {
        $response = Http::timeout(15)->retry(2, 500)->get(self::URL)->throw();
        $xml = @simplexml_load_string($response->body());

        if ($xml === false) {
            throw new RuntimeException('Invalid ECB response.');
        }

        $rates = ['EUR' => '1'];

        foreach ($xml->xpath('//*[@currency]') ?: [] as $cube) {
            $rates[(string) $cube['currency']] = (string) $cube['rate'];
        }

        return $rates;
    }
}
