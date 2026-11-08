<?php

namespace App\Core\Updates;

use App\Core\Eshop;
use RuntimeException;
use ZipArchive;

/**
 * Aktualizačný balík: ZIP s balíkom e-shopu (package.zip) a manifestom (manifest.json).
 *
 * Manifest obsahuje verziu, požiadavky, kontrolný súčet SHA-256 balíka a podpis Ed25519
 * reťazca „eshop-update:{verzia}:{sha256}“ súkromným kľúčom autora.
 */
final class UpdatePackage
{
    /**
     * @param  array{version: string, sha256: string, signature?: ?string, requires?: array{php?: string}, notes?: ?string}  $manifest
     */
    private function __construct(
        public readonly string $path,
        public readonly array $manifest,
        public readonly string $packagePath,
    ) {}

    /**
     * Otvorí balík, rozbalí package.zip do dočasného adresára a overí kontrolný súčet.
     */
    public static function open(string $path, string $workDir): self
    {
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new UpdateException(__('admin.updates.invalid_package'));
        }

        try {
            $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);

            if (! is_array($manifest) || ! is_string($manifest['version'] ?? null) || ! is_string($manifest['sha256'] ?? null)
                || ! preg_match('/^\d+\.\d+\.\d+(-[0-9A-Za-z.]+)?$/', $manifest['version']) || $zip->locateName('package.zip') === false) {
                throw new UpdateException(__('admin.updates.invalid_package'));
            }

            if (! is_dir($workDir) && ! mkdir($workDir, 0775, true) && ! is_dir($workDir)) {
                throw new RuntimeException('Cannot create '.$workDir);
            }

            $packagePath = $workDir.'/package.zip';
            $source = $zip->getStream('package.zip');
            $target = fopen($packagePath, 'wb');
            stream_copy_to_stream($source, $target);
            fclose($target);
            fclose($source);
        } finally {
            $zip->close();
        }

        if (! hash_equals(strtolower($manifest['sha256']), hash_file('sha256', $packagePath))) {
            throw new UpdateException(__('admin.updates.checksum_failed'));
        }

        return new self($path, $manifest, $packagePath);
    }

    public function version(): string
    {
        return $this->manifest['version'];
    }

    public function isSigned(): bool
    {
        return filled($this->manifest['signature'] ?? null);
    }

    /**
     * Overí podpis niektorým z dôveryhodných verejných kľúčov.
     *
     * @param  list<string>  $publicKeys  base64
     */
    public function verifySignature(array $publicKeys): bool
    {
        $signature = base64_decode((string) ($this->manifest['signature'] ?? ''), true);

        if ($signature === false || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
            return false;
        }

        foreach ($publicKeys as $key) {
            $binary = base64_decode($key, true);

            if ($binary !== false && strlen($binary) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
                && sodium_crypto_sign_verify_detached($signature, self::message($this->version(), $this->manifest['sha256']), $binary)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Problémy s kompatibilitou (prázdne = v poriadku).
     *
     * @return list<string>
     */
    public function compatibilityProblems(): array
    {
        $problems = [];

        if (version_compare($this->version(), Eshop::VERSION, '<=')) {
            $problems[] = __('admin.updates.not_newer', ['version' => $this->version(), 'current' => Eshop::VERSION]);
        }

        $php = $this->manifest['requires']['php'] ?? null;

        if (is_string($php) && version_compare(PHP_VERSION, $php, '<')) {
            $problems[] = __('admin.updates.php_too_old', ['required' => $php, 'current' => PHP_VERSION]);
        }

        if (! class_exists(ZipArchive::class)) {
            $problems[] = 'PHP zip';
        }

        return $problems;
    }

    public static function message(string $version, string $sha256): string
    {
        return 'eshop-update:'.$version.':'.strtolower($sha256);
    }
}
