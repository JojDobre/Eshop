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

        $name = ($this->activeResolver)() ?? $this->defaultTheme;
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
     * Zabudne načítanú aktívnu šablónu (napr. po zmene v nastaveniach).
     */
    public function flush(): void
    {
        $this->chain = null;
        $this->themes = null;
    }
}
