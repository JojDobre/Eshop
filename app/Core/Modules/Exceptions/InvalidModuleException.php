<?php

namespace App\Core\Modules\Exceptions;

use RuntimeException;

class InvalidModuleException extends RuntimeException
{
    public static function unreadable(string $path): self
    {
        return new self("Module manifest [{$path}] cannot be read.");
    }

    public static function invalidJson(string $path, string $reason): self
    {
        return new self("Module manifest [{$path}] is not valid JSON: {$reason}.");
    }

    public static function missingKey(string $path, string $key): self
    {
        return new self("Module manifest in [{$path}] is missing required key [{$key}].");
    }

    public static function invalidName(string $path, string $name): self
    {
        return new self("Module name [{$name}] in [{$path}] is invalid. Use lowercase letters, digits and dashes.");
    }
}
