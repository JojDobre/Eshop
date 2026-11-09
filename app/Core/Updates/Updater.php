<?php

namespace App\Core\Updates;

use App\Core\Backup\BackupManager;
use App\Core\Backup\BackupRestorer;
use App\Core\Eshop;
use App\Core\Modules\ModuleManager;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use ZipArchive;

/**
 * Aktualizácia e-shopu na webhostingu bez SSH.
 *
 * Postup: overenie balíka (podpis, kontrolný súčet, kompatibilita) → režim údržby → záloha databázy →
 * uloženie pôvodných súborov na vrátenie → nahradenie súborov → migrácie → kontrola, či obchod beží.
 * Pri chybe sa všetko automaticky vráti. Vrátiť sa dá aj ručne alebo záchranným skriptom (public/rescue.php).
 */
class Updater
{
    /** Súbory a adresáre, ktoré aktualizácia nikdy neprepíše. */
    public const PROTECTED = ['.env', 'storage/', 'public/uploads/', 'bootstrap/cache/'];

    private string $root;

    /** @var Closure(): void */
    private Closure $migrate;

    /** @var Closure(): void */
    private Closure $healthCheck;

    public function __construct(private readonly BackupManager $backups, private readonly BackupRestorer $restorer)
    {
        $this->root = base_path();
        $this->migrate = function (): void {
            if (Artisan::call('migrate', ['--force' => true]) !== 0) {
                throw new UpdateException(trim(Artisan::output()));
            }

            // Aj migrácie modulov, ktoré pribudli v tejto aktualizácii.
            app(ModuleManager::class)->migrateActive();
        };
        $this->healthCheck = function (): void {
            try {
                $response = Http::timeout(15)->withoutVerifying()->get(url('/up'));
            } catch (ConnectionException) {
                // Niektoré webhostingy nedovolia požiadavku na seba samého – kontrola sa preskočí.
                return;
            }

            if ($response->serverError()) {
                throw new UpdateException(__('admin.updates.health_failed', ['status' => $response->status()]));
            }
        };
    }

    /** Pre testy: iný koreňový adresár a kroky. */
    public function using(string $root, ?Closure $migrate = null, ?Closure $healthCheck = null): static
    {
        $this->root = rtrim($root, '/');
        $this->migrate = $migrate ?? $this->migrate;
        $this->healthCheck = $healthCheck ?? $this->healthCheck;

        return $this;
    }

    public function workDirectory(): string
    {
        return $this->root.'/storage/app/updates';
    }

    /**
     * Dôveryhodné verejné kľúče (base64): kľúč autora z jadra a prípadne vlastný z .env.
     *
     * @return list<string>
     */
    public static function publicKeys(): array
    {
        return array_values(array_filter([Eshop::UPDATE_PUBLIC_KEY, (string) config('eshop.updates.public_key')]));
    }

    /**
     * Overí balík a vráti ho (bez inštalácie).
     */
    public function inspect(string $path): UpdatePackage
    {
        $package = UpdatePackage::open($path, $this->workDirectory().'/incoming');

        if ($package->isSigned() && ! $package->verifySignature(self::publicKeys())) {
            throw new UpdateException(__('admin.updates.signature_failed'));
        }

        return $package;
    }

    /**
     * Nainštaluje aktualizáciu. Nepodpísaný balík sa nainštaluje iba s výslovným súhlasom.
     */
    public function install(string $path, bool $allowUnsigned = false): UpdatePackage
    {
        $package = $this->inspect($path);

        if (! $package->isSigned() && ! $allowUnsigned) {
            throw new UpdateException(__('admin.updates.unsigned'));
        }

        if ($problems = $package->compatibilityProblems()) {
            throw new UpdateException(implode(' ', $problems));
        }

        @set_time_limit(0);
        $from = Eshop::VERSION;
        $backup = $this->backups->create(false);
        $this->maintenance(true);

        try {
            $this->replaceFiles($package, $from, $backup);
            ($this->migrate)();
            Artisan::call('optimize:clear');
            ($this->healthCheck)();
        } catch (Throwable $e) {
            Log::error('Update failed, rolling back.', ['exception' => $e, 'version' => $package->version()]);

            try {
                $this->rollback();
            } catch (Throwable $rollbackError) {
                Log::critical('Rollback failed.', ['exception' => $rollbackError]);
            }

            throw new UpdateException(__('admin.updates.failed_rolled_back', ['message' => $e->getMessage()]), previous: $e);
        } finally {
            $this->maintenance(false);
            File::deleteDirectory($this->workDirectory().'/incoming');
        }

        return $package;
    }

    /**
     * Informácie o poslednej aktualizácii, ktorú sa dá vrátiť.
     *
     * @return array{from: string, to: string, backup: string, new_files: list<string>, updated_at: string}|null
     */
    public function lastUpdate(): ?array
    {
        $file = $this->workDirectory().'/rollback.json';
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

        return is_array($data) && is_file($this->workDirectory().'/rollback.zip') ? $data : null;
    }

    /**
     * Vráti poslednú aktualizáciu: pôvodné súbory, zmazanie nových súborov a databázu zo zálohy.
     */
    public function rollback(): void
    {
        $info = $this->lastUpdate() ?? throw new UpdateException(__('admin.updates.nothing_to_rollback'));
        $zip = new ZipArchive;

        if ($zip->open($this->workDirectory().'/rollback.zip', ZipArchive::RDONLY) !== true) {
            throw new UpdateException(__('admin.updates.nothing_to_rollback'));
        }

        try {
            $zip->extractTo($this->root);
        } finally {
            $zip->close();
        }

        foreach ($info['new_files'] as $file) {
            if (! $this->isProtected($file) && ! str_contains($file, '..')) {
                File::delete($this->root.'/'.$file);
                $this->removeEmptyParents(dirname($this->root.'/'.$file));
            }
        }

        if ($info['backup'] !== '') {
            $this->restorer->restore($info['backup'], withFiles: false, safetyBackup: false);
        }

        File::delete([$this->workDirectory().'/rollback.zip', $this->workDirectory().'/rollback.json']);
        Artisan::call('optimize:clear');
    }

    private function replaceFiles(UpdatePackage $package, string $from, string $backup): void
    {
        $source = new ZipArchive;

        if ($source->open($package->packagePath, ZipArchive::RDONLY) !== true) {
            throw new UpdateException(__('admin.updates.invalid_package'));
        }

        File::ensureDirectoryExists($this->workDirectory());
        $rollback = new ZipArchive;
        $rollback->open($this->workDirectory().'/rollback.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $newFiles = [];
        $entries = [];

        // 1. Pôvodné súbory do archívu na vrátenie (pred akoukoľvek zmenou).
        for ($i = 0; $i < $source->numFiles; $i++) {
            $name = (string) $source->getNameIndex($i);
            // Balík má všetko v adresári eshop/.
            $relative = preg_replace('#^eshop/#', '', $name);

            if ($relative === '' || str_ends_with($relative, '/') || str_contains($relative, '..') || $this->isProtected($relative)) {
                continue;
            }

            $entries[$name] = $relative;
            $target = $this->root.'/'.$relative;

            if (is_file($target)) {
                $rollback->addFile($target, $relative);
            } else {
                $newFiles[] = $relative;
            }
        }

        $rollback->close();
        file_put_contents($this->workDirectory().'/rollback.json', json_encode([
            'from' => $from, 'to' => $package->version(), 'backup' => $backup, 'new_files' => $newFiles, 'updated_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // 2. Nové súbory.
        try {
            foreach ($entries as $name => $relative) {
                $target = $this->root.'/'.$relative;
                File::ensureDirectoryExists(dirname($target));
                $stream = $source->getStream($name);
                $temporary = $target.'.update-tmp';
                $handle = fopen($temporary, 'wb');
                stream_copy_to_stream($stream, $handle);
                fclose($handle);
                fclose($stream);
                rename($temporary, $target);

                if (function_exists('opcache_invalidate') && str_ends_with($target, '.php')) {
                    @opcache_invalidate($target, true);
                }
            }
        } finally {
            $source->close();
        }
    }

    /** Po zmazaní nových súborov odstráni aj prázdne adresáre (napr. celý nový modul). */
    private function removeEmptyParents(string $directory): void
    {
        while (str_starts_with($directory, $this->root.'/') && is_dir($directory) && count(scandir($directory) ?: []) === 2) {
            rmdir($directory);
            $directory = dirname($directory);
        }
    }

    private function isProtected(string $relative): bool
    {
        foreach (self::PROTECTED as $protected) {
            if ($relative === rtrim($protected, '/') || str_starts_with($relative, $protected)) {
                return true;
            }
        }

        return false;
    }

    private function maintenance(bool $on): void
    {
        try {
            if ($on) {
                // Návštevníci uvidia stránku údržby, správca pokračuje v tej istej požiadavke.
                Artisan::call('down', ['--retry' => 60]);
            } elseif (app()->isDownForMaintenance()) {
                Artisan::call('up');
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
