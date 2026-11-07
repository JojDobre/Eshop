<?php

namespace App\Core\Content;

use App\Core\Installer\Installation;
use App\Settings\GeneralSettings;
use Throwable;

/**
 * Jazyky obchodu: predvolený (adresy bez predpony) a ďalšie (adresy s predponou, napr. /en/…).
 */
class ShopLocales
{
    /** @var list<string>|null */
    private ?array $enabled = null;

    private ?string $default = null;

    public function default(): string
    {
        $this->load();

        return $this->default;
    }

    /**
     * Zapnuté jazyky, predvolený je prvý.
     *
     * @return list<string>
     */
    public function enabled(): array
    {
        $this->load();

        return $this->enabled;
    }

    public function isEnabled(string $locale): bool
    {
        return in_array($locale, $this->enabled(), true);
    }

    public function isMultilingual(): bool
    {
        return count($this->enabled()) > 1;
    }

    /** Predpona jazyka v adrese (čeština → „cz"). */
    public function urlCode(string $locale): string
    {
        return (string) (config('eshop.locale_url_codes.'.$locale) ?? $locale);
    }

    /** Jazyk podľa predpony v adrese (iba zapnuté jazyky). */
    public function fromUrlCode(string $code): ?string
    {
        foreach ($this->enabled() as $locale) {
            if ($this->urlCode($locale) === $code) {
                return $locale;
            }
        }

        return null;
    }

    /** Názov jazyka v danom jazyku (Slovenčina, English…). */
    public function label(string $locale): string
    {
        return (string) (config('eshop.content_locales.'.$locale) ?? strtoupper($locale));
    }

    /** Vynúti nové načítanie (po zmene nastavení). */
    public function refresh(): void
    {
        $this->enabled = null;
        $this->default = null;
    }

    private function load(): void
    {
        if ($this->enabled !== null) {
            return;
        }

        $default = (string) config('app.locale', 'sk');
        $others = [];

        if (app(Installation::class)->isInstalled()) {
            try {
                $settings = app(GeneralSettings::class);
                $default = $settings->default_locale;
                $others = $settings->locales_enabled ? ($settings->locales ?? []) : [];
            } catch (Throwable) {
                // Databáza nie je dostupná: iba predvolený jazyk.
            }
        }

        $this->default = $default;
        $this->enabled = array_values(array_unique([$default, ...array_filter($others, fn ($locale) => is_string($locale) && $locale !== '')]));
    }
}
