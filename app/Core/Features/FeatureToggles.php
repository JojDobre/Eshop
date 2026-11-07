<?php

namespace App\Core\Features;

/**
 * Register prepínačov funkcií. Jadro a moduly sem pridávajú funkcie, ktoré sa dajú vypnúť.
 */
class FeatureToggles
{
    /** @var array<string, FeatureToggle> */
    private array $toggles = [];

    /** @var array<string, \Closure(): string> */
    private array $groups = [];

    /**
     * @param  \Closure(): string  $label
     */
    public function group(string $key, \Closure $label): void
    {
        $this->groups[$key] = $label;
    }

    public function register(FeatureToggle $toggle): void
    {
        $this->toggles[$toggle->key] = $toggle;
    }

    /**
     * @return array<string, array{label: string, toggles: list<FeatureToggle>}>
     */
    public function grouped(): array
    {
        $grouped = [];

        foreach ($this->toggles as $toggle) {
            $grouped[$toggle->group] ??= ['label' => isset($this->groups[$toggle->group]) ? ($this->groups[$toggle->group])() : $toggle->group, 'toggles' => []];
            $grouped[$toggle->group]['toggles'][] = $toggle;
        }

        return $grouped;
    }

    public function find(string $key): ?FeatureToggle
    {
        return $this->toggles[$key] ?? null;
    }
}
