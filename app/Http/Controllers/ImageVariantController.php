<?php

namespace App\Http\Controllers;

use App\Core\Media\ResponsiveImage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Prvé vyžiadanie zmenšenej verzie obrázka: vytvorí ju a uloží ako statický súbor (ďalej ju servíruje webový server).
 */
class ImageVariantController
{
    public function __invoke(int $width, string $path, ResponsiveImage $images): BinaryFileResponse
    {
        $file = $images->generate(substr($path, 0, -strlen('.webp')), $width);

        abort_if($file === null, 404);

        return response()->file($file, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
