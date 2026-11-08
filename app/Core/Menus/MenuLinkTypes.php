<?php

namespace App\Core\Menus;

/**
 * Register typov odkazov v ponukách. Jadro pozná iba vlastnú adresu (url), ostatné pridajú moduly.
 */
class MenuLinkTypes
{
    /** @var array<string, MenuLinkType> */
    private array $types = [];

    public function register(MenuLinkType $type): void
    {
        $this->types[$type->key] = $type;
    }

    public function find(string $key): ?MenuLinkType
    {
        return $this->types[$key] ?? null;
    }

    /**
     * @return array<string, MenuLinkType>
     */
    public function all(): array
    {
        return $this->types;
    }
}
