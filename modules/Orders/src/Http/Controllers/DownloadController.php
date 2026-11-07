<?php

namespace Modules\Orders\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Models\ProductFile;
use Modules\Orders\Models\DownloadLink;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Stiahnutie súboru digitálneho produktu cez odkaz z e-mailu alebo stránky objednávky.
 */
class DownloadController extends Controller
{
    public function __invoke(string $token): StreamedResponse
    {
        $link = DownloadLink::query()->where('token', $token)->with(['order', 'file'])->firstOrFail();

        abort_unless($link->isUsable(), 410, __('orders::messages.download_unavailable'));
        abort_if($link->file === null || ! $link->file->exists(), 404);

        $link->increment('downloads', 1, ['last_downloaded_at' => now()]);

        return Storage::disk(ProductFile::DISK)->download($link->file->path, $link->file_name);
    }
}
