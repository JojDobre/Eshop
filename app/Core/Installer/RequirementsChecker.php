<?php

namespace App\Core\Installer;

use App\Core\Eshop;

/**
 * Overí, či webhosting spĺňa požiadavky e-shopu.
 */
class RequirementsChecker
{
    public const REQUIRED_EXTENSIONS = [
        'pdo_mysql', 'mbstring', 'openssl', 'intl', 'curl', 'zip', 'fileinfo',
        'sodium', 'bcmath', 'xml', 'dom', 'ctype', 'tokenizer', 'json',
    ];

    public function __construct(private readonly string $basePath) {}

    /**
     * @return list<Requirement>
     */
    public function check(): array
    {
        $results = [
            new Requirement(
                key: 'php',
                label: 'PHP '.Eshop::MIN_PHP_VERSION.'+',
                passed: version_compare(PHP_VERSION, Eshop::MIN_PHP_VERSION, '>='),
                current: PHP_VERSION,
            ),
        ];

        foreach (self::REQUIRED_EXTENSIONS as $extension) {
            $results[] = new Requirement(
                key: 'ext-'.$extension,
                label: $extension,
                passed: extension_loaded($extension),
            );
        }

        $results[] = new Requirement(
            key: 'ext-image',
            label: 'gd / imagick',
            passed: extension_loaded('gd') || extension_loaded('imagick'),
        );

        foreach (['storage', 'storage/app', 'storage/framework', 'storage/logs', 'bootstrap/cache', 'public/uploads'] as $directory) {
            $results[] = new Requirement(
                key: 'writable-'.$directory,
                label: $directory.'/',
                passed: is_dir($this->basePath.'/'.$directory) && is_writable($this->basePath.'/'.$directory),
                group: 'writable',
            );
        }

        $envFile = $this->basePath.'/.env';
        $results[] = new Requirement(
            key: 'writable-env',
            label: '.env',
            passed: is_file($envFile) ? is_writable($envFile) : is_writable($this->basePath),
            group: 'writable',
        );

        $memory = self::bytes((string) ini_get('memory_limit'));
        $results[] = new Requirement(
            key: 'memory_limit',
            label: 'memory_limit ≥ 128M',
            passed: $memory < 0 || $memory >= 128 * 1024 * 1024,
            current: $memory < 0 ? '∞' : (string) ini_get('memory_limit'),
            required: false,
            group: 'limits',
        );

        $time = (int) ini_get('max_execution_time');
        $results[] = new Requirement(
            key: 'max_execution_time',
            label: 'max_execution_time ≥ 30 s',
            passed: $time === 0 || $time >= 30,
            current: $time === 0 ? '∞' : $time.' s',
            required: false,
            group: 'limits',
        );

        $upload = self::bytes((string) ini_get('upload_max_filesize'));
        $results[] = new Requirement(
            key: 'upload_max_filesize',
            label: 'upload_max_filesize ≥ 16M',
            passed: $upload < 0 || $upload >= 16 * 1024 * 1024,
            current: (string) ini_get('upload_max_filesize'),
            required: false,
            group: 'limits',
        );

        return $results;
    }

    /**
     * Sú splnené všetky povinné požiadavky?
     *
     * @param  list<Requirement>|null  $results
     */
    public function passes(?array $results = null): bool
    {
        foreach ($results ?? $this->check() as $requirement) {
            if ($requirement->required && ! $requirement->passed) {
                return false;
            }
        }

        return true;
    }

    /**
     * Prevedie hodnotu z php.ini (napr. „128M") na bajty. -1 znamená bez limitu.
     */
    public static function bytes(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '-1') {
            return -1;
        }

        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
