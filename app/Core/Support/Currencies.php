<?php

namespace App\Core\Support;

use App\Core\Installer\Installation;
use App\Settings\CurrencySettings;
use App\Settings\GeneralSettings;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Money\Currency;
use Throwable;

/**
 * Meny obchodu a prepočet cien kurzom.
 *
 * Cena zadaná ručne v danej mene má vždy prednosť; prepočet sa použije, iba keď chýba.
 */
class Currencies
{
    public const ROUND_MATH = 'math';

    public const ROUND_WHOLE = 'whole';

    public const ROUND_NINETY_NINE = 'ninety_nine';

    private ?string $default = null;

    /** @var list<string>|null */
    private ?array $enabled = null;

    /** @var array<string, string> */
    private array $rates = [];

    /** @var array<string, string> */
    private array $rounding = [];

    public function default(): string
    {
        $this->load();

        return $this->default;
    }

    /**
     * Zapnuté meny, predvolená je prvá.
     *
     * @return list<string>
     */
    public function enabled(): array
    {
        $this->load();

        return $this->enabled;
    }

    public function isEnabled(string $currency): bool
    {
        return in_array(strtoupper($currency), $this->enabled(), true);
    }

    public function isMulticurrency(): bool
    {
        return count($this->enabled()) > 1;
    }

    /** Koľko jednotiek meny je 1 jednotka predvolenej meny. */
    public function rate(string $currency): string
    {
        $this->load();

        return $currency === $this->default ? '1' : ($this->rates[$currency] ?? '1');
    }

    public function hasRate(string $currency): bool
    {
        $this->load();

        return $currency === $this->default || isset($this->rates[$currency]);
    }

    /**
     * Prepočet sumy (najmenšie jednotky) z jednej meny do druhej, so zaokrúhlením cieľovej meny.
     */
    public function convert(int $amount, string $from, string $to, bool $applyRounding = true): int
    {
        if ($from === $to) {
            return $amount;
        }

        $fromScale = $this->scale($from);
        $toScale = $this->scale($to);

        $value = BigDecimal::ofUnscaledValue($amount, $fromScale)
            ->dividedBy($this->rate($from), 12, RoundingMode::HalfUp)
            ->multipliedBy($this->rate($to));

        $converted = $value->toScale($toScale, RoundingMode::HalfUp)->getUnscaledValue()->toInt();

        return $applyRounding ? $this->round($converted, $to) : $converted;
    }

    /**
     * Zaokrúhlenie prepočítanej ceny podľa nastavenia meny (na celé jednotky, na „,99“).
     */
    public function round(int $amount, string $currency): int
    {
        $this->load();
        $unit = 10 ** $this->scale($currency);

        if ($unit === 1 || $amount <= 0) {
            return $amount;
        }

        return match ($this->rounding[$currency] ?? self::ROUND_MATH) {
            self::ROUND_WHOLE => (int) (round($amount / $unit) * $unit),
            self::ROUND_NINETY_NINE => (int) (ceil($amount / $unit) * $unit) - 1,
            default => $amount,
        };
    }

    public function refresh(): void
    {
        $this->default = null;
        $this->enabled = null;
    }

    private function scale(string $currency): int
    {
        try {
            return Currency::of($currency)->getDefaultFractionDigits();
        } catch (Throwable) {
            return 2;
        }
    }

    private function load(): void
    {
        if ($this->enabled !== null) {
            return;
        }

        $default = 'EUR';
        $others = [];
        $this->rates = [];
        $this->rounding = [];

        if (app(Installation::class)->isInstalled()) {
            try {
                $default = app(GeneralSettings::class)->default_currency;
                $settings = app(CurrencySettings::class);
                $others = $settings->multicurrency_enabled ? $settings->enabled : [];
                $this->rounding = $settings->rounding;

                foreach ($settings->rates as $currency => $rate) {
                    if (is_numeric($rate) && (float) $rate > 0) {
                        $this->rates[$currency] = (string) BigDecimal::of((string) $rate);
                    }
                }
            } catch (Throwable) {
                // Databáza nie je dostupná: iba predvolená mena.
            }
        }

        $this->default = $default;
        // Mena bez kurzu sa nedá ponúknuť (ceny by sa nedali prepočítať).
        $this->enabled = array_values(array_unique([$default, ...array_filter($others, fn ($currency) => is_string($currency) && isset($this->rates[$currency]))]));
    }
}
