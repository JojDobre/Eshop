<?php

namespace App\Core\Media;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Zmenšené verzie obrázkov vo formáte WebP pre rôzne šírky obrazovky (srcset).
 *
 * Verzia leží ako statický súbor v public/uploads/_v/{šírka}/{cesta}.webp, takže ju po prvom vytvorení
 * servíruje priamo webový server. Ak ešte neexistuje, požiadavka prejde do Laravelu a verzia sa vytvorí (GD).
 * Prehliadač si podľa šírky a hustoty pixelov displeja vyberie dostatočne veľkú verziu – obrázky sú ostré
 * aj na mobile s Retina displejom, no nesťahuje sa zbytočne veľký originál.
 */
class ResponsiveImage
{
    /** Povolené šírky verzií (v pixeloch). */
    public const WIDTHS = [320, 480, 640, 800, 1080, 1440, 1920, 2560];

    public const DIRECTORY = '_v';

    private const QUEUE_KEY = 'img-variants-pending';

    /** Kvalita WebP (vysoká, aby nevznikali artefakty). */
    public const QUALITY = 84;

    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Hodnota atribútu srcset pre obrázok z verejného disku (cesta alebo adresa), alebo null.
     */
    public function srcset(?string $image): ?string
    {
        $path = $this->pathFromUrl($image);

        if ($path === null || ! $this->enabled()) {
            return null;
        }

        $width = $this->originalWidth($path);

        if ($width === null) {
            return null;
        }

        $largest = max(self::WIDTHS);
        $wanted = $this->widthsFor($width);
        // Ponúkajú sa iba verzie, ktoré už existujú (niektoré servery chýbajúci súbor v /uploads do Laravelu nepošlú).
        $existing = array_filter($wanted, fn (int $candidate) => is_file($this->variantPath($path, $candidate)));

        if (count($existing) < count($wanted)) {
            $this->queue($path);
        }

        if ($existing === [] || ($width > $largest && ! in_array($largest, $existing, true))) {
            return null;
        }

        $candidates = collect($existing)
            ->reject(fn (int $candidate) => $candidate === $largest && $width > $largest)
            ->map(fn (int $candidate) => $this->url($path, $candidate).' '.$candidate.'w');

        // Najväčšou možnosťou je samotný originál (bez straty kvality); obrovské fotky sa zmenšia na 2560 px.
        $candidates->push($width <= $largest
            ? Storage::disk('public')->url($path).' '.$width.'w'
            : $this->url($path, $largest).' '.$largest.'w');

        return $candidates->implode(', ');
    }

    /**
     * Šírky verzií pre obrázok danej šírky (menšie ako originál; pri obrovských fotkách aj 2560 px).
     *
     * @return list<int>
     */
    public function widthsFor(int $width): array
    {
        $largest = max(self::WIDTHS);

        return array_values(array_filter(self::WIDTHS, fn (int $candidate) => $candidate < $width || ($candidate === $largest && $width > $largest)));
    }

    /**
     * Vytvorí všetky verzie obrázka (pri nahratí a v plánovanej úlohe). Vráti počet nových súborov.
     */
    public function generateAll(string $path): int
    {
        $width = $this->isResizable($path) ? $this->originalWidth($path) : null;
        $created = 0;

        foreach ($width ? $this->widthsFor($width) : [] as $candidate) {
            if (! is_file($this->variantPath($path, $candidate)) && $this->generate($path, $candidate) !== null) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * Plánovaná úloha: dogeneruje verzie obrázkov, ktoré sa zobrazili a ešte ich nemajú (s časovým limitom).
     */
    public function processQueue(int $seconds = 40): int
    {
        $started = microtime(true);
        $pending = Cache::pull(self::QUEUE_KEY, []);
        $processed = 0;

        foreach ($pending as $index => $path) {
            if (microtime(true) - $started > $seconds) {
                // Zvyšok sa spracuje pri ďalšom spustení.
                Cache::put(self::QUEUE_KEY, array_values(array_unique([...array_slice($pending, $index), ...Cache::get(self::QUEUE_KEY, [])])), now()->addDay());

                break;
            }

            $this->generateAll($path);
            $processed++;
        }

        return $processed;
    }

    private function queue(string $path): void
    {
        $pending = Cache::get(self::QUEUE_KEY, []);

        if (! in_array($path, $pending, true) && count($pending) < 2000) {
            $pending[] = $path;
            Cache::put(self::QUEUE_KEY, $pending, now()->addDay());
        }
    }

    private function variantPath(string $path, int $width): string
    {
        return Storage::disk('public')->path(self::DIRECTORY.'/'.$width.'/'.$path.'.webp');
    }

    /** Adresa verzie danej šírky. */
    public function url(string $path, int $width): string
    {
        return Storage::disk('public')->url(self::DIRECTORY.'/'.$width.'/'.$path.'.webp');
    }

    /**
     * Cesta na verejnom disku z adresy obrázka (iba obrázky, ktoré sa dajú zmenšiť).
     */
    public function pathFromUrl(?string $image): ?string
    {
        if (! is_string($image) || $image === '') {
            return null;
        }

        $base = rtrim(Storage::disk('public')->url(''), '/').'/';

        if (str_starts_with($image, $base)) {
            $image = substr($image, strlen($base));
        } elseif (str_starts_with($image, '/uploads/')) {
            $image = substr($image, strlen('/uploads/'));
        } elseif (preg_match('#^[a-z]+://#i', $image)) {
            return null;
        }

        $image = rawurldecode(ltrim((string) strtok($image, '?'), '/'));

        return $this->isResizable($image) ? $image : null;
    }

    public function isResizable(string $path): bool
    {
        return $path !== ''
            && ! str_contains($path, '..')
            && ! str_starts_with($path, self::DIRECTORY.'/')
            && in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::EXTENSIONS, true);
    }

    /**
     * Vytvorí verziu (ak ešte neexistuje) a vráti cestu k súboru.
     */
    public function generate(string $path, int $width): ?string
    {
        if (! in_array($width, self::WIDTHS, true) || ! $this->isResizable($path)) {
            return null;
        }

        $source = Storage::disk('public')->path($path);
        $target = $this->variantPath($path, $width);

        if (is_file($target)) {
            return $target;
        }

        if (! is_file($source) || ! $this->enabled()) {
            return null;
        }

        $info = @getimagesize($source);

        if ($info === false || $info[0] < 1 || $info[1] < 1) {
            return null;
        }

        // Veľké fotky z fotoaparátu potrebujú viac pamäte, než majú lacné hostingy predvolene.
        @ini_set('memory_limit', '512M');

        try {
            $image = match ($info[2]) {
                IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
                IMAGETYPE_PNG => @imagecreatefrompng($source),
                IMAGETYPE_WEBP => @imagecreatefromwebp($source),
                default => false,
            };

            if ($image === false) {
                return null;
            }

            $image = $this->orient($image, $source, $info[2]);
            $sourceWidth = imagesx($image);
            $sourceHeight = imagesy($image);
            $targetWidth = min($width, $sourceWidth);
            $targetHeight = max(1, (int) round($sourceHeight * $targetWidth / $sourceWidth));

            $resized = imagecreatetruecolor($targetWidth, $targetHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagefill($resized, 0, 0, imagecolorallocatealpha($resized, 255, 255, 255, 127));
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
            imagedestroy($image);

            File::ensureDirectoryExists(dirname($target));
            $temporary = $target.'.'.getmypid().'.tmp';
            $saved = imagewebp($resized, $temporary, self::QUALITY);
            imagedestroy($resized);

            if (! $saved) {
                @unlink($temporary);

                return null;
            }

            rename($temporary, $target);

            return $target;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Zmaže všetky verzie obrázka (po zmazaní alebo nahradení originálu).
     */
    public function forget(string $path): void
    {
        foreach (self::WIDTHS as $width) {
            Storage::disk('public')->delete(self::DIRECTORY.'/'.$width.'/'.$path.'.webp');
        }

        Cache::forget($this->dimensionsKey($path));
    }

    public function enabled(): bool
    {
        return function_exists('imagewebp') && function_exists('imagecreatefromjpeg');
    }

    private function originalWidth(string $path): ?int
    {
        $width = Cache::rememberForever($this->dimensionsKey($path), function () use ($path): int {
            $file = Storage::disk('public')->path($path);
            $info = is_file($file) ? @getimagesize($file) : false;

            if ($info === false) {
                return 0;
            }

            // Fotka otočená podľa EXIF má vymenenú šírku a výšku.
            return in_array($this->exifOrientation($file, $info[2]), [5, 6, 7, 8], true) ? (int) $info[1] : (int) $info[0];
        });

        return $width > 0 ? $width : null;
    }

    private function dimensionsKey(string $path): string
    {
        return 'img-width-'.md5($path.'|'.@filemtime(Storage::disk('public')->path($path)));
    }

    /** Otočí fotku z mobilu podľa EXIF orientácie (WebP verzia EXIF neobsahuje). */
    private function orient(\GdImage $image, string $source, int $type): \GdImage
    {
        return match ($this->exifOrientation($source, $type)) {
            3 => imagerotate($image, 180, 0) ?: $image,
            6 => imagerotate($image, -90, 0) ?: $image,
            8 => imagerotate($image, 90, 0) ?: $image,
            default => $image,
        };
    }

    private function exifOrientation(string $file, int $type): int
    {
        if ($type !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return 1;
        }

        $exif = @exif_read_data($file);

        return is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
    }
}
