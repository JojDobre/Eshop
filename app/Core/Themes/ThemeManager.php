<?php

namespace App\Core\Themes;

use Closure;

/**
 * Nájde šablóny vzhľadu a určí poradie, v akom sa v nich hľadajú súbory.
 *
 * Poradie: aktívna šablóna → jej rodič (child theme) → … → predvolená šablóna.
 * Súbor, ktorý v aktívnej šablóne chýba, sa tak vezme z rodiča alebo z predvolenej šablóny.
 */
class ThemeManager
{
    /** @var array<string, Theme>|null */
    private ?array $themes = null;

    /** @var list<Theme>|null */
    private ?array $chain = null;

    /** Šablóna v náhľade (iba pre administrátora, pred aktiváciou). */
    private ?string $preview = null;

    /**
     * @param  Closure(): ?string  $activeResolver  Vráti názov aktívnej šablóny (z nastavení obchodu).
     */
    public function __construct(
        private readonly string $themesPath,
        private readonly string $defaultTheme,
        private readonly Closure $activeResolver,
    ) {}

    /**
     * @return array<string, Theme>
     */
    public function all(): array
    {
        if ($this->themes !== null) {
            return $this->themes;
        }

        $themes = [];

        foreach (glob(rtrim($this->themesPath, '/').'/*/theme.json') ?: [] as $manifest) {
            $theme = Theme::fromFile($manifest);

            if ($theme !== null && ! isset($themes[$theme->name])) {
                $themes[$theme->name] = $theme;
            }
        }

        ksort($themes);

        return $this->themes = $themes;
    }

    public function find(string $name): ?Theme
    {
        return $this->all()[$name] ?? null;
    }

    public function active(): ?Theme
    {
        return $this->chain()[0] ?? null;
    }

    /**
     * Aktívna šablóna a všetky šablóny, z ktorých preberá súbory, v poradí hľadania.
     *
     * @return list<Theme>
     */
    public function chain(): array
    {
        if ($this->chain !== null) {
            return $this->chain;
        }

        $name = $this->preview ?? ($this->activeResolver)() ?? $this->defaultTheme;
        $theme = $this->find($name) ?? $this->find($this->defaultTheme);

        $chain = [];
        $seen = [];

        while ($theme !== null && ! isset($seen[$theme->name])) {
            $chain[] = $theme;
            $seen[$theme->name] = true;
            $theme = $theme->parent !== null ? $this->find($theme->parent) : null;
        }

        $default = $this->find($this->defaultTheme);

        if ($default !== null && ! isset($seen[$default->name])) {
            $chain[] = $default;
        }

        return $this->chain = $chain;
    }

    /**
     * Adresáre, v ktorých sa hľadajú šablóny obchodu (menný priestor „theme::").
     *
     * @return list<string>
     */
    public function viewPaths(): array
    {
        return array_map(fn (Theme $theme) => $theme->path, $this->chain());
    }

    /**
     * Adresáre, v ktorých môže šablóna prepísať šablóny modulu.
     * Napr. themes/default/modules/storefront/home.blade.php prepíše view('storefront::home').
     *
     * @return list<string>
     */
    public function moduleOverridePaths(string $module): array
    {
        return array_map(fn (Theme $theme) => $theme->path.'/modules/'.$module, $this->chain());
    }

    /**
     * Adresár s prekladmi šablóny (prvý v poradí hľadania, ktorý existuje).
     */
    public function langPath(): ?string
    {
        foreach ($this->chain() as $theme) {
            if (is_dir($theme->path.'/lang')) {
                return $theme->path.'/lang';
            }
        }

        return null;
    }

    /**
     * Prvý existujúci súbor zo zoznamu (výber šablóny podľa obsahu), napr.
     * template('product', ['product-kava', 'product-type-4']) skúsi templates/product-kava,
     * templates/product-type-4 a nakoniec templates/product.
     *
     * @param  list<string|null>  $specific
     */
    public function template(string $base, array $specific = []): string
    {
        foreach (array_filter($specific) as $name) {
            if (preg_match('/^[a-z0-9-]+$/', (string) $name) && view()->exists('theme::templates.'.$name)) {
                return 'theme::templates.'.$name;
            }
        }

        return 'theme::templates.'.$base;
    }

    /**
     * Definície nastavení šablóny vrátane zdedených od rodiča (odvodená šablóna ich môže zmeniť alebo doplniť).
     *
     * @return array<string, array<string, mixed>>
     */
    public function settingDefinitions(?Theme $theme = null): array
    {
        return $this->merged($theme, fn (Theme $item) => $item->settings);
    }

    /**
     * Miesta na ponuky v šablóne (aj zdedené).
     *
     * @return array<string, array<string, string>>
     */
    public function menus(?Theme $theme = null): array
    {
        return $this->merged($theme, fn (Theme $item) => $item->menus);
    }

    /**
     * Oblasti na bloky v šablóne (aj zdedené).
     *
     * @return array<string, array<string, string>>
     */
    public function areas(?Theme $theme = null): array
    {
        return $this->merged($theme, fn (Theme $item) => $item->areas);
    }

    /**
     * Rodičovský reťazec ľubovoľnej šablóny (pre náhľad a nastavenia neaktívnej šablóny).
     *
     * @return list<Theme>
     */
    public function chainFor(Theme $theme): array
    {
        $chain = [];
        $seen = [];

        while ($theme !== null && ! isset($seen[$theme->name])) {
            $chain[] = $theme;
            $seen[$theme->name] = true;
            $theme = $theme->parent !== null ? $this->find($theme->parent) : null;
        }

        return $chain;
    }

    /**
     * @param  Closure(Theme): array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function merged(?Theme $theme, Closure $values): array
    {
        $chain = $theme ? $this->chainFor($theme) : $this->chain();
        $merged = [];

        foreach (array_reverse($chain) as $item) {
            $merged = [...$merged, ...$values($item)];
        }

        return $merged;
    }

    /**
     * Adresáre s prekladmi šablóny od najvzdialenejšieho rodiča po aktívnu šablónu
     * (preklady odvodenej šablóny prepíšu preklady rodiča).
     *
     * @return list<string>
     */
    public function langPaths(): array
    {
        return array_values(array_filter(array_map(fn (Theme $theme) => $theme->path.'/lang', array_reverse($this->chain())), 'is_dir'));
    }

    /**
     * Zobrazí inú šablónu namiesto aktívnej (náhľad pre administrátora).
     */
    public function usePreview(?string $name): void
    {
        $this->preview = $name !== null && $this->find($name) !== null ? $name : null;
        $this->chain = null;
    }

    public function previewing(): ?string
    {
        return $this->preview;
    }

    /** Názov uloženej aktívnej šablóny (bez náhľadu). */
    public function activeName(): string
    {
        $name = ($this->activeResolver)() ?? $this->defaultTheme;

        return $this->find($name) ? $name : $this->defaultTheme;
    }

    /**
     * Zabudne načítanú aktívnu šablónu (napr. po zmene v nastaveniach).
     */
    public function flush(): void
    {
        $this->chain = null;
        $this->themes = null;
    }
}
