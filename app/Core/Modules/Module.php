<?php

namespace App\Core\Modules;

use App\Core\Modules\Exceptions\InvalidModuleException;
use JsonException;

/**
 * Popis jedného modulu načítaný zo súboru module.json.
 */
final class Module
{
    /**
     * @param  array<string, string>  $title  Názov podľa jazyka, napr. ['sk' => 'Obchod'].
     * @param  array<string, string>  $description  Popis podľa jazyka.
     * @param  array<string, string>  $requiredModules  Závislosti: názov modulu => verzia (semver obmedzenie).
     * @param  list<string>  $permissions  Oprávnenia, ktoré modul registruje.
     * @param  array<string, bool>  $sizes  Predvoľba podľa veľkosti obchodu (small/medium/large).
     */
    public function __construct(
        public readonly string $name,
        public readonly string $version,
        public readonly string $path,
        public readonly ?string $provider,
        public readonly array $title = [],
        public readonly array $description = [],
        public readonly bool $core = false,
        public readonly bool $enabledByDefault = true,
        public readonly ?string $requiredEshopVersion = null,
        public readonly array $requiredModules = [],
        public readonly array $permissions = [],
        public readonly ?string $namespace = null,
        public readonly array $sizes = [],
    ) {}

    public static function fromFile(string $manifestPath): self
    {
        $contents = @file_get_contents($manifestPath);

        if ($contents === false) {
            throw InvalidModuleException::unreadable($manifestPath);
        }

        try {
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw InvalidModuleException::invalidJson($manifestPath, $e->getMessage());
        }

        if (! is_array($data)) {
            throw InvalidModuleException::invalidJson($manifestPath, 'root must be an object');
        }

        return self::fromArray($data, dirname($manifestPath));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, string $path): self
    {
        foreach (['name', 'version'] as $key) {
            if (! isset($data[$key]) || ! is_string($data[$key]) || $data[$key] === '') {
                throw InvalidModuleException::missingKey($path, $key);
            }
        }

        if (! preg_match('/^[a-z][a-z0-9-]*$/', $data['name'])) {
            throw InvalidModuleException::invalidName($path, $data['name']);
        }

        $requires = is_array($data['requires'] ?? null) ? $data['requires'] : [];

        return new self(
            name: $data['name'],
            version: $data['version'],
            path: $path,
            provider: isset($data['provider']) && is_string($data['provider']) ? $data['provider'] : null,
            title: self::stringMap($data['title'] ?? []),
            description: self::stringMap($data['description'] ?? []),
            core: (bool) ($data['core'] ?? false),
            enabledByDefault: (bool) ($data['enabled'] ?? true),
            requiredEshopVersion: isset($requires['eshop']) && is_string($requires['eshop']) ? $requires['eshop'] : null,
            requiredModules: self::stringMap($requires['modules'] ?? []),
            permissions: array_values(array_filter($data['permissions'] ?? [], 'is_string')),
            namespace: self::namespaceFor($data, $path),
            sizes: array_map('boolval', array_intersect_key(is_array($data['sizes'] ?? null) ? $data['sizes'] : [], array_flip(['small', 'medium', 'large']))),
        );
    }

    /**
     * Adresár so zdrojovým kódom modulu (PSR-4 koreň pre jeho menný priestor).
     */
    public function sourcePath(): string
    {
        return $this->path.'/src';
    }

    /**
     * Menný priestor tried modulu: z „namespace" v module.json, inak Modules\{NázovAdresára}\.
     *
     * @param  array<string, mixed>  $data
     */
    private static function namespaceFor(array $data, string $path): string
    {
        if (isset($data['namespace']) && is_string($data['namespace']) && $data['namespace'] !== '') {
            return trim($data['namespace'], '\\').'\\';
        }

        $directory = str_replace(['-', '_'], ' ', basename($path));

        return 'Modules\\'.str_replace(' ', '', ucwords($directory)).'\\';
    }

    public function title(?string $locale = null): string
    {
        return $this->translated($this->title, $locale) ?? $this->name;
    }

    public function description(?string $locale = null): string
    {
        return $this->translated($this->description, $locale) ?? '';
    }

    /**
     * @param  array<string, string>  $values
     */
    private function translated(array $values, ?string $locale): ?string
    {
        if ($values === []) {
            return null;
        }

        $locale ??= function_exists('app') && app()->bound('translator') ? app()->getLocale() : 'en';

        return $values[$locale] ?? $values['en'] ?? reset($values);
    }

    /**
     * @return array<string, string>
     */
    private static function stringMap(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $result = [];

        foreach ($value as $key => $item) {
            if (is_string($key) && is_string($item)) {
                $result[$key] = $item;
            }
        }

        return $result;
    }
}
