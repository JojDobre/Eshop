<?php

namespace App\Http\Controllers;

use App\Core\System\CronHeartbeat;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;

/**
 * Webový cron: pre hostingy, ktoré nevedia spúšťať príkazy, stačí volať túto adresu
 * (napr. službou typu cron-job.org) každých 5–15 minút.
 */
class CronController
{
    public function __invoke(string $token): Response
    {
        abort_unless(hash_equals(CronHeartbeat::webToken(), $token), 404);

        Artisan::call('schedule:run');

        return response('OK', 200, ['Content-Type' => 'text/plain', 'X-Robots-Tag' => 'noindex']);
    }
}
