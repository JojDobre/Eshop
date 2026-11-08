<?php

namespace Modules\Storefront\Cookies;

use Throwable;

/**
 * Súhlas s voliteľnými cookies (analytika, marketing). Nevyhnutné cookies (relácia, košík)
 * súhlas nepotrebujú. Voľba zákazníka je v cookie „eshop_consent“, ktorú nastaví lišta.
 */
class CookieConsent
{
    public const COOKIE = 'eshop_consent';

    public const ANALYTICS = 'analytics';

    public const MARKETING = 'marketing';

    /**
     * Voliteľné kategórie, ktoré obchod naozaj používa.
     *
     * @return list<string>
     */
    public function categories(): array
    {
        $settings = $this->settings();

        return array_values(array_filter([
            filled($settings?->analytics_code) ? self::ANALYTICS : null,
            filled($settings?->marketing_code) ? self::MARKETING : null,
        ]));
    }

    /** Používa obchod lištu? */
    public function isActive(): bool
    {
        return ($this->settings()?->enabled ?? false) || $this->categories() !== [];
    }

    public function hasDecided(): bool
    {
        return $this->choice() !== null;
    }

    public function allows(string $category): bool
    {
        return (bool) ($this->choice()[$category] ?? false);
    }

    /**
     * Kódy, ktoré sa smú vložiť do stránky (podľa súhlasu).
     *
     * @return list<string>
     */
    public function allowedCodes(): array
    {
        $settings = $this->settings();
        $codes = [
            $this->allows(self::ANALYTICS) ? $settings?->analytics_code : null,
            $this->allows(self::MARKETING) ? $settings?->marketing_code : null,
        ];

        // Kódy z modulov (napr. Google Analytics 4, Meta Pixel z modulu Marketing).
        foreach (self::$providers as [$category, $provider]) {
            if ($this->allows($category)) {
                $codes[] = $provider();
            }
        }

        return array_values(array_filter($codes, fn ($code) => is_string($code) && trim($code) !== ''));
    }

    /** @var array<string, array{0: string, 1: \Closure(): ?string}> */
    private static array $providers = [];

    /**
     * Pridá kód, ktorý sa vloží do stránky iba so súhlasom pre danú kategóriu (analytics / marketing).
     *
     * @param  \Closure(): ?string  $provider
     */
    public static function addCode(string $key, string $category, \Closure $provider): void
    {
        self::$providers[$key] = [$category, $provider];
    }

    /**
     * @return array<string, bool>|null
     */
    private function choice(): ?array
    {
        $raw = request()->cookies->get(self::COOKIE);

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $data = json_decode($raw, true);

        return is_array($data) ? array_map(fn ($value) => (bool) $value, array_intersect_key($data, array_flip([self::ANALYTICS, self::MARKETING]))) : null;
    }

    private function settings(): ?CookieSettings
    {
        try {
            return app(CookieSettings::class);
        } catch (Throwable) {
            return null;
        }
    }
}
