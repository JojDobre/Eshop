<?php

namespace App\Core\Themes;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Hodnoty nastavení šablóny (farby, rozloženie…). Každá šablóna má vlastné uložené hodnoty,
 * chýbajúce hodnoty sa doplnia predvolenými z theme.json.
 */
class ThemeOptions
{
    /** @var array<string, array<string, mixed>> */
    private array $stored = [];

    /** @var array<string, mixed>|null Neuložené hodnoty pre živý náhľad v administrácii. */
    private ?array $preview = null;

    public function __construct(private readonly ThemeManager $themes) {}

    /**
     * Hodnoty pre šablónu (predvolene aktívnu).
     *
     * @return array<string, mixed>
     */
    public function values(?Theme $theme = null): array
    {
        $theme ??= $this->themes->active();

        if ($theme === null) {
            return [];
        }

        $definitions = $this->themes->settingDefinitions($theme);
        $stored = $this->stored($theme->name);

        if ($this->preview !== null && $theme->name === $this->themes->active()?->name) {
            $stored = [...$stored, ...$this->preview];
        }

        $values = [];

        foreach ($definitions as $key => $definition) {
            $values[$key] = $this->normalize($definition, $stored[$key] ?? $definition['default']);
        }

        return $values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values()[$key] ?? $default;
    }

    /**
     * Adresa obrázka z nastavenia typu image.
     */
    public function imageUrl(string $key): ?string
    {
        $path = $this->get($key);

        return is_string($path) && $path !== '' ? Storage::disk('public')->url($path) : null;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function save(Theme $theme, array $values): void
    {
        $definitions = $this->themes->settingDefinitions($theme);
        $clean = [];

        foreach ($definitions as $key => $definition) {
            if (array_key_exists($key, $values)) {
                $clean[$key] = $this->normalize($definition, $values[$key]);
            }
        }

        DB::table('theme_settings')->updateOrInsert(
            ['theme' => $theme->name],
            ['values' => json_encode($clean, JSON_UNESCAPED_UNICODE), 'updated_at' => now(), 'created_at' => now()],
        );

        unset($this->stored[$theme->name]);
    }

    /** Zabudne uložené hodnoty šablóny (návrat na predvolené). */
    public function reset(Theme $theme): void
    {
        DB::table('theme_settings')->where('theme', $theme->name)->delete();
        unset($this->stored[$theme->name]);
    }

    /**
     * Neuložené hodnoty pre živý náhľad (platí iba pre aktuálnu požiadavku).
     *
     * @param  array<string, mixed>|null  $values
     */
    public function preview(?array $values): void
    {
        $this->preview = $values;
    }

    /**
     * @return array<string, mixed>
     */
    private function stored(string $theme): array
    {
        if (! array_key_exists($theme, $this->stored)) {
            try {
                $json = Schema::hasTable('theme_settings') ? DB::table('theme_settings')->where('theme', $theme)->value('values') : null;
                $this->stored[$theme] = is_string($json) ? (array) json_decode($json, true) : [];
            } catch (Throwable) {
                $this->stored[$theme] = [];
            }
        }

        return $this->stored[$theme];
    }

    /**
     * Hodnota zodpovedajúca typu nastavenia (neplatná hodnota = predvolená).
     *
     * @param  array<string, mixed>  $definition
     */
    private function normalize(array $definition, mixed $value): mixed
    {
        $default = $definition['default'];

        return match ($definition['type']) {
            'color' => is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : $default,
            'select' => is_string($value) && array_key_exists($value, $definition['options']) ? $value : $default,
            'toggle' => (bool) $value,
            'number' => is_numeric($value) ? $value + 0 : $default,
            'image' => is_string($value) && $value !== '' && ! str_contains($value, '..') ? $value : null,
            default => is_scalar($value) ? mb_substr((string) $value, 0, 500) : $default,
        };
    }
}
