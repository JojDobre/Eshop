<?php

namespace App\Core\Modules;

use App\Core\Modules\Exceptions\InvalidModuleException;
use App\Core\Modules\Exceptions\ModuleException;
use Composer\Semver\Semver;
use Illuminate\Support\Facades\Artisan;
use Throwable;
use UnexpectedValueException;

/**
 * Nájde moduly, určí, ktoré sú zapnuté, skontroluje závislosti a poradie načítania.
 *
 * Toto je jediná vlastná vrstva nad Laravelom pre modulárnosť. Samotný modul je
 * obyčajný Laravel Service Provider (pozri ModuleServiceProvider).
 */
class ModuleManager
{
    /** @var array<string, Module>|null */
    private ?array $modules = null;

    /** @var list<Module>|null */
    private ?array $bootOrder = null;

    /**
     * Problémy zistené pri načítaní (neplatný manifest, chýbajúca závislosť, …).
     *
     * @var array<string, string>
     */
    private array $problems = [];

    /**
     * @param  list<string>  $paths  Adresáre, v ktorých sa hľadajú moduly (každý podadresár s module.json).
     * @param  bool  $safeMode  Záchranný režim: načítajú sa iba základné (core) moduly.
     */
    public function __construct(
        private readonly array $paths,
        private readonly ModuleStateStore $state,
        private readonly string $eshopVersion,
        private readonly bool $safeMode = false,
    ) {}

    /**
     * Všetky nájdené moduly zoradené podľa názvu.
     *
     * @return array<string, Module>
     */
    public function all(): array
    {
        if ($this->modules !== null) {
            return $this->modules;
        }

        $modules = [];

        foreach ($this->paths as $path) {
            foreach (glob(rtrim($path, '/').'/*/module.json') ?: [] as $manifest) {
                try {
                    $module = Module::fromFile($manifest);
                } catch (InvalidModuleException $e) {
                    $this->problems[basename(dirname($manifest))] = $e->getMessage();

                    continue;
                }

                if (isset($modules[$module->name])) {
                    $this->problems[$module->name] = "Module [{$module->name}] is defined more than once.";

                    continue;
                }

                $modules[$module->name] = $module;
            }
        }

        ksort($modules);

        return $this->modules = $modules;
    }

    public function find(string $name): ?Module
    {
        return $this->all()[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return $this->find($name) !== null;
    }

    public function isSafeMode(): bool
    {
        return $this->safeMode;
    }

    /**
     * Je modul zapnutý podľa uloženého stavu (bez ohľadu na to, či sa dá načítať)?
     */
    public function isEnabled(string $name): bool
    {
        $module = $this->find($name);

        if ($module === null) {
            return false;
        }

        if ($module->core) {
            return true;
        }

        return $this->state->get($name) ?? $module->enabledByDefault;
    }

    /**
     * Načíta sa modul v tejto požiadavke?
     */
    public function isActive(string $name): bool
    {
        foreach ($this->active() as $module) {
            if ($module->name === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Moduly, ktoré sa majú načítať, v poradí podľa závislostí.
     * Moduly s chýbajúcou závislosťou alebo nekompatibilnou verziou sa vynechajú
     * a dôvod sa zapíše medzi problémy.
     *
     * @return list<Module>
     */
    public function active(): array
    {
        if ($this->bootOrder !== null) {
            return $this->bootOrder;
        }

        $candidates = [];

        foreach ($this->all() as $name => $module) {
            if ($this->safeMode && ! $module->core) {
                continue;
            }

            if (! $this->isEnabled($name)) {
                continue;
            }

            if (! $this->isCompatible($module)) {
                $this->problems[$name] = ModuleException::incompatible(
                    $name, (string) $module->requiredEshopVersion, $this->eshopVersion,
                )->getMessage();

                continue;
            }

            $candidates[$name] = $module;
        }

        // Opakovane vyraďujeme moduly s nesplnenými závislosťami, kým sa množina neustáli
        // (vyradenie jedného modulu môže znefunkčniť ďalší, ktorý od neho závisí).
        do {
            $removed = false;

            foreach ($candidates as $name => $module) {
                $missing = $this->unsatisfiedDependencies($module, $candidates);

                if ($missing !== []) {
                    $this->problems[$name] = ModuleException::missingDependencies($name, $missing)->getMessage();
                    unset($candidates[$name]);
                    $removed = true;
                }
            }
        } while ($removed);

        return $this->bootOrder = $this->sortByDependencies($candidates);
    }

    /**
     * Zapne modul. Vyhodí výnimku, ak modul neexistuje, nie je kompatibilný
     * alebo nemá zapnuté závislosti.
     */
    public function enable(string $name): void
    {
        $module = $this->find($name) ?? throw ModuleException::notFound($name);

        if (! $this->isCompatible($module)) {
            throw ModuleException::incompatible($name, (string) $module->requiredEshopVersion, $this->eshopVersion);
        }

        $enabled = array_filter($this->all(), fn (Module $m) => $m->name !== $name && $this->isEnabled($m->name));
        $missing = $this->unsatisfiedDependencies($module, $enabled);

        if ($missing !== []) {
            throw ModuleException::missingDependencies($name, $missing);
        }

        $this->state->set($name, true);
        $this->bootOrder = null;

        $this->migrate($module);
    }

    /**
     * Spustí migrácie modulu (tabuľky a nastavenia). Moduly vypnuté pri inštalácii ich ešte nemajú;
     * už spustené migrácie sa preskočia.
     */
    public function migrate(Module $module): void
    {
        $path = $module->path.'/database/migrations';

        if (! is_dir($path) || ! function_exists('app') || ! app()->bound('migrator')) {
            return;
        }

        $exitCode = Artisan::call('migrate', ['--path' => $path, '--realpath' => true, '--force' => true]);

        if ($exitCode !== 0) {
            throw new \RuntimeException(trim(Artisan::output()) ?: 'Module migration failed: '.$module->name);
        }
    }

    /**
     * Vypne modul. Dáta modulu sa nemažú. Vyhodí výnimku pri základnom module
     * alebo ak od modulu závisí iný zapnutý modul.
     */
    public function disable(string $name): void
    {
        $module = $this->find($name) ?? throw ModuleException::notFound($name);

        if ($module->core) {
            throw ModuleException::coreModule($name);
        }

        $dependents = $this->enabledDependents($name);

        if ($dependents !== []) {
            throw ModuleException::hasDependents($name, $dependents);
        }

        $this->state->set($name, false);
        $this->bootOrder = null;
    }

    /**
     * Zapnuté moduly, ktoré priamo závisia od daného modulu.
     *
     * @return list<string>
     */
    public function enabledDependents(string $name): array
    {
        $dependents = [];

        foreach ($this->all() as $module) {
            if ($module->name !== $name
                && array_key_exists($name, $module->requiredModules)
                && $this->isEnabled($module->name)) {
                $dependents[] = $module->name;
            }
        }

        return $dependents;
    }

    public function isCompatible(Module $module): bool
    {
        if ($module->requiredEshopVersion === null) {
            return true;
        }

        return $this->satisfies($this->eshopVersion, $module->requiredEshopVersion);
    }

    /**
     * @return array<string, string>
     */
    public function problems(): array
    {
        $this->active();

        return $this->problems;
    }

    public function problem(string $name): ?string
    {
        return $this->problems()[$name] ?? null;
    }

    /**
     * Zaznamená problém, ktorý vznikol pri registrácii modulu (napr. výnimka v Service Provideri).
     */
    public function reportProblem(string $name, string $message): void
    {
        $this->problems[$name] = $message;
    }

    /**
     * @param  array<string, Module>  $available
     * @return list<string>
     */
    private function unsatisfiedDependencies(Module $module, array $available): array
    {
        $missing = [];

        foreach ($module->requiredModules as $dependency => $constraint) {
            $candidate = $available[$dependency] ?? null;

            if ($candidate === null || ! $this->satisfies($candidate->version, $constraint)) {
                $missing[] = $dependency;
            }
        }

        return $missing;
    }

    /**
     * Topologické zoradenie: závislosti sa načítajú pred modulmi, ktoré ich potrebujú.
     *
     * @param  array<string, Module>  $modules
     * @return list<Module>
     */
    private function sortByDependencies(array $modules): array
    {
        $sorted = [];
        $state = []; // 1 = práve sa spracúva, 2 = hotovo

        $visit = function (string $name, array $path) use (&$visit, &$sorted, &$state, $modules): bool {
            if (($state[$name] ?? 0) === 2) {
                return true;
            }

            if (($state[$name] ?? 0) === 1) {
                $cycle = implode(' -> ', [...$path, $name]);

                foreach ($path as $member) {
                    $this->problems[$member] = "Circular module dependency: {$cycle}.";
                }

                return false;
            }

            $state[$name] = 1;

            foreach (array_keys($modules[$name]->requiredModules) as $dependency) {
                if (isset($modules[$dependency]) && ! $visit($dependency, [...$path, $name])) {
                    $state[$name] = 2;

                    return false;
                }
            }

            $state[$name] = 2;
            $sorted[$name] = $modules[$name];

            return true;
        };

        foreach (array_keys($modules) as $name) {
            $visit($name, []);
        }

        // Moduly, ktoré sú súčasťou cyklu, sa nenačítajú.
        return array_values(array_filter(
            $sorted,
            fn (Module $module) => ! str_starts_with($this->problems[$module->name] ?? '', 'Circular'),
        ));
    }

    private function satisfies(string $version, string $constraint): bool
    {
        try {
            return Semver::satisfies($version, $constraint);
        } catch (UnexpectedValueException|Throwable) {
            return false;
        }
    }
}
