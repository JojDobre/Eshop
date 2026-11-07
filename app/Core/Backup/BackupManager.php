<?php

namespace App\Core\Backup;

use App\Settings\SystemSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Throwable;
use ZipArchive;

/**
 * Zálohy obchodu: databáza (SQL), nahrané súbory a nastavenia (.env, stav modulov) v jednom ZIP súbore.
 * Zálohy sú v storage/app/backups – mimo verejného adresára.
 */
class BackupManager
{
    public function __construct(private readonly DatabaseDumper $dumper) {}

    public function directory(): string
    {
        return (string) config('eshop.backup_path', storage_path('app/backups'));
    }

    /**
     * Vytvorí zálohu a vráti názov súboru.
     */
    public function create(bool $withFiles = true): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP extension zip is missing.');
        }

        File::ensureDirectoryExists($this->directory());
        // Ochrana pre prípad, že by bol adresár storage dostupný z webu.
        File::put($this->directory().'/.htaccess', "Require all denied\nDeny from all\n");

        $name = 'eshop-'.now()->format('Y-m-d-His').'.zip';
        $sql = tempnam(sys_get_temp_dir(), 'eshop-sql');

        try {
            $this->dumper->dump($sql);

            $zip = new ZipArchive;

            if ($zip->open($this->directory().'/'.$name, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Cannot create backup archive.');
            }

            $zip->addFile($sql, 'database.sql');

            // .env obsahuje APP_KEY – bez neho sa po obnove nedajú prečítať šifrované nastavenia (heslá, kľúče).
            if (is_file(base_path('.env'))) {
                $zip->addFile(base_path('.env'), 'env.txt');
            }

            foreach (glob(storage_path('app/eshop/*.json')) ?: [] as $file) {
                $zip->addFile($file, 'eshop/'.basename($file));
            }

            if ($withFiles && is_dir(public_path('uploads'))) {
                foreach (Finder::create()->files()->in(public_path('uploads'))->ignoreDotFiles(false) as $file) {
                    $zip->addFile($file->getRealPath(), 'uploads/'.str_replace('\\', '/', $file->getRelativePathname()));
                }
            }

            $zip->setArchiveComment('E-shop backup '.now()->toIso8601String());
            $zip->close();
        } catch (Throwable $e) {
            File::delete($this->directory().'/'.$name);

            throw $e;
        } finally {
            File::delete($sql);
        }

        $this->prune();

        return $name;
    }

    /**
     * @return list<array{name: string, size: int, created_at: Carbon}>
     */
    public function all(): array
    {
        return collect(glob($this->directory().'/eshop-*.zip') ?: [])
            ->map(fn (string $path) => [
                'name' => basename($path),
                'size' => (int) filesize($path),
                'created_at' => Carbon::createFromFormat('Y-m-d-His', substr(basename($path), 6, 17)) ?: Carbon::createFromTimestamp((int) filemtime($path)),
            ])
            // Názov obsahuje čas vytvorenia (eshop-RRRR-MM-DD-HHMMSS.zip).
            ->sortByDesc('name')
            ->values()
            ->all();
    }

    public function path(string $name): string
    {
        // Iba súbory záloh z tohto adresára (ochrana proti ../).
        if (! preg_match('/^eshop-[0-9-]+\.zip$/', $name) || ! is_file($this->directory().'/'.$name)) {
            throw new RuntimeException('Backup not found.');
        }

        return $this->directory().'/'.$name;
    }

    public function delete(string $name): void
    {
        File::delete($this->path($name));
    }

    /**
     * Ponechá iba posledných N záloh.
     */
    public function prune(): void
    {
        $keep = max(1, app(SystemSettings::class)->backups_keep);

        foreach (array_slice($this->all(), $keep) as $backup) {
            File::delete($this->directory().'/'.$backup['name']);
        }
    }
}
