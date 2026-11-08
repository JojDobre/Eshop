<?php

namespace Modules\Marketing\Http\Controllers;

use App\Core\Content\ContentLocale;
use App\Core\Content\ShopLocales;
use App\Core\Support\Currencies;
use App\Core\Support\CurrencyContext;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Modules\Marketing\Feeds\ProductFeed;
use Modules\Marketing\MarketingSettings;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * XML feed v jazyku a mene z parametrov (?lang=cs&currency=CZK). Súbor sa generuje najviac raz za hodinu.
 */
class FeedController extends Controller
{
    public const TTL_MINUTES = 60;

    public function __invoke(Request $request, string $type): BinaryFileResponse
    {
        abort_unless((bool) MarketingSettings::get('feeds_enabled'), 404);

        $locales = app(ShopLocales::class);
        $locale = $locales->isEnabled((string) $request->query('lang')) ? (string) $request->query('lang') : $locales->default();
        $currencies = app(Currencies::class);
        $currency = in_array(strtoupper((string) $request->query('currency')), $currencies->enabled(), true) ? strtoupper((string) $request->query('currency')) : $currencies->default();

        $path = storage_path('app/private/feeds/'.$type.'-'.$locale.'-'.$currency.'.xml');

        if (! is_file($path) || filemtime($path) < time() - self::TTL_MINUTES * 60) {
            app(ContentLocale::class)->set($locale);
            app()->setLocale($locale);
            app(CurrencyContext::class)->set($currency);
            File::ensureDirectoryExists(dirname($path));

            // Do dočasného súboru a potom premenovanie, aby nikto nedostal napoly zapísaný feed.
            $temporary = $path.'.'.getmypid().'.tmp';
            (new ProductFeed((bool) MarketingSettings::get('feeds_out_of_stock', true)))->write($type, $temporary);
            rename($temporary, $path);
        }

        return response()->file($path, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /** Vymaže uložené feedy (po zmene nastavení). */
    public static function flush(): void
    {
        File::deleteDirectory(storage_path('app/private/feeds'));
    }
}
