<?php

namespace App\Core\Installer;

final class DatabaseCheckResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $version = null,
        public readonly ?string $error = null,
        /** MySQL 5.7: funguje, ale neodporúča sa. */
        public readonly bool $limitedSupport = false,
        /** Databáza už obsahuje tabuľky inej inštalácie s rovnakým prefixom. */
        public readonly bool $containsInstallation = false,
    ) {}

    public static function failed(string $error, ?string $version = null): self
    {
        return new self(success: false, version: $version, error: $error);
    }
}
