<?php

namespace Modules\Tax\Vies;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Tax\EuVat;
use Throwable;

/**
 * Overenie IČ DPH v systéme VIES Európskej komisie.
 */
class ViesClient
{
    public const URL = 'https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number';

    /**
     * @return bool|null true = platné, false = neplatné, null = VIES je nedostupný
     */
    public function check(string $country, string $vatId): ?bool
    {
        $prefix = EuVat::vatPrefix(strtoupper($country));
        $number = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $vatId));

        if (str_starts_with($number, $prefix)) {
            $number = substr($number, strlen($prefix));
        }

        if ($number === '') {
            return false;
        }

        $key = 'vies:'.$prefix.$number;

        if (($cached = Cache::get($key)) !== null) {
            return (bool) $cached;
        }

        try {
            $response = Http::timeout(10)->acceptJson()->post(self::URL, ['countryCode' => $prefix, 'vatNumber' => $number])->throw();
            $valid = $response->json('valid');
        } catch (Throwable $e) {
            Log::warning('VIES check failed.', ['vat_id' => $prefix.$number, 'exception' => $e->getMessage()]);

            return null;
        }

        if (! is_bool($valid)) {
            return null;
        }

        // Výsledok sa pamätá deň (VIES býva preťažený).
        Cache::put($key, $valid, now()->addDay());

        return $valid;
    }
}
