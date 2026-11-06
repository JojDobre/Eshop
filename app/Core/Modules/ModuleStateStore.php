<?php

namespace App\Core\Modules;

/**
 * Ukladá, ktoré moduly sú zapnuté alebo vypnuté.
 *
 * Stav je v JSON súbore (nie v databáze), aby sa dal načítať už pri štarte aplikácie,
 * ešte pred inštaláciou a aj vtedy, keď databáza nie je dostupná.
 */
class ModuleStateStore
{
    /** @var array<string, bool>|null */
    private ?array $states = null;

    public function __construct(private readonly string $path) {}

    /**
     * Vráti uložený stav modulu, alebo null, ak modul ešte nemá uložený stav.
     */
    public function get(string $module): ?bool
    {
        return $this->all()[$module] ?? null;
    }

    public function set(string $module, bool $enabled): void
    {
        $states = $this->all();
        $states[$module] = $enabled;
        ksort($states);

        $this->write($states);
    }

    /**
     * @return array<string, bool>
     */
    public function all(): array
    {
        if ($this->states !== null) {
            return $this->states;
        }

        if (! is_file($this->path)) {
            return $this->states = [];
        }

        $data = json_decode((string) @file_get_contents($this->path), true);

        $states = [];

        if (is_array($data) && is_array($data['modules'] ?? null)) {
            foreach ($data['modules'] as $name => $enabled) {
                if (is_string($name)) {
                    $states[$name] = (bool) $enabled;
                }
            }
        }

        return $this->states = $states;
    }

    /**
     * @param  array<string, bool>  $states
     */
    private function write(array $states): void
    {
        $directory = dirname($this->path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $json = json_encode(['modules' => $states], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Zápis cez dočasný súbor, aby sa pri výpadku nepoškodil existujúci stav.
        $temporary = $this->path.'.'.bin2hex(random_bytes(4)).'.tmp';
        file_put_contents($temporary, $json."\n", LOCK_EX);
        rename($temporary, $this->path);

        $this->states = $states;
    }
}
