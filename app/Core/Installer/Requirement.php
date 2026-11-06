<?php

namespace App\Core\Installer;

final class Requirement
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly bool $passed,
        public readonly ?string $current = null,
        /** Povinná požiadavka (inak iba odporúčanie). */
        public readonly bool $required = true,
        /** Skupina: php, writable, limits. */
        public readonly string $group = 'php',
    ) {}
}
