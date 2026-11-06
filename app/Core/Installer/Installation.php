<?php

namespace App\Core\Installer;

/**
 * Informácia o tom, či je e-shop nainštalovaný.
 *
 * Stav je v súbore installed.json (nie v databáze), aby sa dal overiť rýchlo
 * a bez pripojenia k databáze.
 */
class Installation
{
    private ?bool $installed = null;

    public function __construct(private readonly string $markerPath) {}

    public function isInstalled(): bool
    {
        return $this->installed ??= is_file($this->markerPath);
    }

    public function markInstalled(string $version): void
    {
        $directory = dirname($this->markerPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($this->markerPath, json_encode([
            'version' => $version,
            'installed_at' => date(DATE_ATOM),
        ], JSON_PRETTY_PRINT)."\n", LOCK_EX);

        $this->installed = true;
    }

    /**
     * @return array{version?: string, installed_at?: string}
     */
    public function info(): array
    {
        if (! $this->isInstalled()) {
            return [];
        }

        $data = json_decode((string) @file_get_contents($this->markerPath), true);

        return is_array($data) ? $data : [];
    }

    public function markerPath(): string
    {
        return $this->markerPath;
    }
}
