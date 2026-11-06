<?php

namespace App\Core\Installer;

use RuntimeException;

/**
 * Bezpečne upraví hodnoty v súbore .env (zachová ostatné riadky a komentáre).
 */
class EnvironmentWriter
{
    public function __construct(private readonly string $path) {}

    /**
     * @param  array<string, string|int|bool|null>  $values
     */
    public function set(array $values): void
    {
        $contents = is_file($this->path) ? (string) file_get_contents($this->path) : '';
        $lines = $contents === '' ? [] : preg_split('/\r\n|\n|\r/', rtrim($contents, "\r\n"));

        foreach ($values as $key => $value) {
            if (! preg_match('/^[A-Z][A-Z0-9_]*$/', $key)) {
                throw new RuntimeException("Invalid environment variable name [{$key}].");
            }

            $line = $key.'='.self::format($value);
            $replaced = false;

            foreach ($lines as $index => $existing) {
                if (preg_match('/^\s*(export\s+)?'.preg_quote($key, '/').'\s*=/', $existing)) {
                    $lines[$index] = $line;
                    $replaced = true;
                }
            }

            if (! $replaced) {
                $lines[] = $line;
            }
        }

        $temporary = $this->path.'.'.bin2hex(random_bytes(4)).'.tmp';

        if (file_put_contents($temporary, implode("\n", $lines)."\n", LOCK_EX) === false) {
            throw new RuntimeException("Unable to write [{$this->path}].");
        }

        @chmod($temporary, 0600);
        rename($temporary, $this->path);
    }

    public function isWritable(): bool
    {
        return is_file($this->path) ? is_writable($this->path) : is_writable(dirname($this->path));
    }

    /**
     * Naformátuje hodnotu tak, aby ju knižnica phpdotenv načítala presne v pôvodnej podobe.
     */
    public static function format(string|int|bool|null $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $value = str_replace(["\r", "\n"], '', (string) $value);

        if ($value === '') {
            return '';
        }

        if (preg_match('/^[A-Za-z0-9_.,:\/@+\-]+$/', $value) && ! in_array(strtolower($value), ['null', 'true', 'false', 'empty'], true)) {
            return $value;
        }

        return '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"';
    }
}
