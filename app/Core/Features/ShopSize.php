<?php

namespace App\Core\Features;

use App\Core\Modules\ModuleManager;
use App\Settings\GeneralSettings;
use Throwable;

/**
 * Veľkosť obchodu: predvolený výber funkcií pre malý, stredný a veľký obchod.
 *
 * Každý prepínač funkcie (FeatureToggle::$sizes) a voliteľný modul („sizes“ v module.json)
 * určí, či sa pri danej veľkosti zapne alebo vypne. Ostatné funkcie sú voliteľné a veľkosť ich nemení.
 * Keď obchodník zmení niektorú funkciu ručne, obchod je „vlastný“.
 */
class ShopSize
{
    public const SIZES = ['small', 'medium', 'large'];

    public const CUSTOM = 'custom';

    public function __construct(private readonly FeatureToggles $toggles, private readonly ModuleManager $modules) {}

    /** Zvolená veľkosť, alebo „custom“, ak sa funkcie od nej líšia. */
    public function current(): string
    {
        $size = $this->stored();

        return $size !== null && $this->changes($size) === ['enable' => [], 'disable' => []] ? $size : self::CUSTOM;
    }

    public function stored(): ?string
    {
        try {
            $size = app(GeneralSettings::class)->shop_size;
        } catch (Throwable) {
            return null;
        }

        return in_array($size, self::SIZES, true) ? $size : null;
    }

    /**
     * Čo sa pri prechode na veľkosť zapne a vypne (názvy na zobrazenie).
     *
     * @return array{enable: list<string>, disable: list<string>}
     */
    public function changes(string $size): array
    {
        $changes = ['enable' => [], 'disable' => []];

        foreach ($this->targets($size) as $target) {
            if ($target['wanted'] !== $target['enabled']) {
                $changes[$target['wanted'] ? 'enable' : 'disable'][] = $target['label'];
            }
        }

        return $changes;
    }

    /**
     * Prepne funkcie podľa veľkosti a uloží ju. Vráti chyby funkcií, ktoré sa nedali prepnúť
     * (napr. chýbajúce nastavenie alebo závislosť).
     *
     * @return list<string>
     */
    public function apply(string $size): array
    {
        $errors = [];
        $targets = $this->targets($size);

        // Najprv vypnutie (závislé funkcie a moduly), potom zapnutie (závislosti).
        foreach ([false, true] as $wanted) {
            foreach ($targets as $target) {
                if ($target['wanted'] === $wanted && $target['enabled'] !== $wanted) {
                    try {
                        ($target['set'])($wanted);
                    } catch (Throwable $e) {
                        $errors[] = $target['label'].': '.$e->getMessage();
                    }
                }
            }
        }

        $this->store($size);

        return $errors;
    }

    /**
     * Použije veľkosť zvolenú v inštalátore (počas inštalácie ešte nie sú načítané moduly a ich funkcie).
     */
    public function applyPending(): void
    {
        try {
            $settings = app(GeneralSettings::class);
            $pending = $settings->shop_size_pending;
        } catch (Throwable) {
            // Nastavenia ešte nie sú zmigrované (nová verzia nahratá, aktualizácia databázy čaká).
            // Nič sa nedeje, pokus sa zopakuje pri ďalšej požiadavke.
            return;
        }

        if (! $pending) {
            return;
        }

        try {
            $settings->shop_size_pending = false;
            $settings->save();
            $this->apply($settings->shop_size);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function store(string $size): void
    {
        $settings = app(GeneralSettings::class);
        $settings->shop_size = in_array($size, self::SIZES, true) ? $size : self::CUSTOM;
        $settings->save();
    }

    /**
     * @return list<array{label: string, enabled: bool, wanted: bool, set: \Closure(bool): void}>
     */
    private function targets(string $size): array
    {
        $targets = [];

        foreach ($this->toggles->grouped() as $group) {
            foreach ($group['toggles'] as $toggle) {
                if (array_key_exists($size, $toggle->sizes)) {
                    $targets[] = [
                        'label' => ($toggle->label)(),
                        'enabled' => (bool) ($toggle->isEnabled)(),
                        'wanted' => $toggle->sizes[$size],
                        'set' => $toggle->set,
                    ];
                }
            }
        }

        foreach ($this->modules->all() as $module) {
            if (! $module->core && array_key_exists($size, $module->sizes)) {
                $targets[] = [
                    'label' => $module->title(),
                    'enabled' => $this->modules->isEnabled($module->name),
                    'wanted' => $module->sizes[$size],
                    'set' => fn (bool $enabled) => $enabled ? $this->modules->enable($module->name) : $this->modules->disable($module->name),
                ];
            }
        }

        return $targets;
    }
}
