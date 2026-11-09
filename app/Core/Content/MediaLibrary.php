<?php

namespace App\Core\Content;

use App\Models\MediaFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Knižnica médií: eviduje všetky obrázky nahraté v administrácii (produkty, kategórie, blog, bloky, šablóna,
 * editor obsahu), aby sa dali znova použiť bez opätovného nahrávania. Súbor sa zo servera zmaže iba vtedy,
 * keď sa nikde nepoužíva.
 */
class MediaLibrary
{
    /** Priečinky verejného disku s obrázkami obchodu. */
    public const DIRECTORIES = ['media', 'demo', 'products', 'categories', 'blog', 'blocks', 'theme', 'branding'];

    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'avif'];

    /** Tabuľky a stĺpce, v ktorých môže byť uložená cesta k súboru (obrázky, bloky, formátovaný text). */
    private const USAGE_COLUMNS = [
        'products' => ['images', 'description', 'short_description'],
        'product_variants' => ['images'],
        'categories' => ['image', 'description', 'blocks'],
        'blog_posts' => ['cover_image', 'blocks', 'excerpt'],
        'pages' => ['content', 'blocks'],
        'block_layouts' => ['blocks'],
        'theme_settings' => ['values'],
        'settings' => ['payload'],
        'email_templates' => ['body'],
    ];

    /**
     * Zaradí súbor z verejného disku do knižnice (ak tam ešte nie je).
     */
    public function register(string $path, ?string $name = null): ?MediaFile
    {
        $path = ltrim($path, '/');

        if ($path === '' || ! $this->isImagePath($path)) {
            return null;
        }

        try {
            if ($existing = MediaFile::query()->where('path', $path)->first()) {
                return $existing;
            }

            if (! Storage::disk('public')->exists($path)) {
                return null;
            }

            return MediaFile::query()->create([
                'disk' => 'public',
                'path' => $path,
                'name' => $name ?: basename($path),
                'uploaded_by' => auth()->id(),
            ]);
        } catch (Throwable $e) {
            // Knižnica nesmie zablokovať uloženie produktu či stránky.
            report($e);

            return null;
        }
    }

    /**
     * Doplní do knižnice obrázky, ktoré sú na disku, ale nie sú v nej (napr. nahraté pred verziou 1.2),
     * a odstráni záznamy súborov, ktoré už neexistujú. Vráti počet pridaných súborov.
     */
    public function sync(): int
    {
        $disk = Storage::disk('public');
        $known = MediaFile::query()->pluck('path')->flip();
        $added = 0;

        foreach (self::DIRECTORIES as $directory) {
            if (! $disk->directoryExists($directory)) {
                continue;
            }

            foreach ($disk->allFiles($directory) as $path) {
                if (! isset($known[$path]) && $this->isImagePath($path) && $this->register($path)) {
                    $added++;
                }
            }
        }

        MediaFile::query()->where('disk', 'public')->get(['id', 'disk', 'path'])
            ->reject(fn (MediaFile $file) => $disk->exists($file->path))
            ->each(fn (MediaFile $file) => $file->deleteQuietly());

        return $added;
    }

    /** Synchronizácia najviac raz za pár minút (pri otvorení knižnice alebo výbere z nej). */
    public function syncOccasionally(): void
    {
        if (Cache::add('media-library-synced', true, now()->addMinutes(5))) {
            rescue(fn () => $this->sync(), report: true);
        }
    }

    /**
     * Či sa súbor niekde používa (produkt, kategória, článok, stránka, blok, nastavenia šablóny…).
     */
    public function isUsed(MediaFile $file): bool
    {
        // Cesta môže byť uložená aj v JSON so zakódovanými lomkami.
        $needles = [$file->path, str_replace('/', '\\/', $file->path)];

        foreach (self::USAGE_COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $columns = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn($table, $column)));

            if ($columns === []) {
                continue;
            }

            // MySQL/MariaDB berú spätnú lomku v LIKE ako únikový znak, SQLite nie.
            $mysql = DB::connection()->getDriverName() !== 'sqlite';

            $query = DB::table($table)->where(function ($query) use ($columns, $needles, $mysql): void {
                foreach ($columns as $column) {
                    foreach ($needles as $needle) {
                        $query->orWhere($column, 'like', '%'.($mysql ? str_replace('\\', '\\\\', $needle) : $needle).'%');
                    }
                }
            });

            if ($query->exists()) {
                return true;
            }
        }

        return false;
    }

    public function isImagePath(string $path): bool
    {
        return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::IMAGE_EXTENSIONS, true);
    }
}
