<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Súbor v knižnici (verejne dostupný v public/uploads/media).
 *
 * @property int $id
 * @property string $disk
 * @property string $path
 * @property string $name
 * @property string|null $mime_type
 * @property int $size
 * @property int|null $width
 * @property int|null $height
 * @property string|null $alt
 */
class MediaFile extends Model
{
    public const DIRECTORY = 'media';

    protected $guarded = [];

    protected static function booted(): void
    {
        // Doplní údaje o súbore pri nahratí.
        static::creating(function (MediaFile $file): void {
            $storage = Storage::disk($file->disk ?: 'public');
            $file->disk = $file->disk ?: 'public';
            $file->name = $file->name ?: basename($file->path);
            $file->mime_type = $storage->mimeType($file->path) ?: null;
            $file->size = (int) $storage->size($file->path);

            if (str_starts_with((string) $file->mime_type, 'image/') && ($dimensions = @getimagesize($storage->path($file->path)))) {
                [$file->width, $file->height] = $dimensions;
            }
        });

        // Zmazaním záznamu sa zmaže aj súbor.
        static::deleted(fn (MediaFile $file) => Storage::disk($file->disk)->delete($file->path));
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
