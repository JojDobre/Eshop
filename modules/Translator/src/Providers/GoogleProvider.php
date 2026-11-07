<?php

namespace Modules\Translator\Providers;

use Illuminate\Support\Facades\Http;

/**
 * Google Cloud Translation API (v2, kľúč API).
 */
class GoogleProvider implements TranslationProvider
{
    public const URL = 'https://translation.googleapis.com/language/translate/v2';

    public function __construct(private readonly string $key) {}

    public function translate(array $texts, string $from, string $to): array
    {
        $response = Http::timeout(60)->asJson()
            ->post(self::URL.'?key='.urlencode($this->key), [
                'q' => array_values($texts),
                'source' => $from,
                'target' => $to,
                'format' => 'html',
            ])
            ->throw();

        return array_map(fn (array $item) => (string) $item['translatedText'], $response->json('data.translations', []));
    }

    public function protect(string $term): string
    {
        return '<span translate="no" class="notranslate">'.$term.'</span>';
    }

    public function unprotect(string $text): string
    {
        return (string) preg_replace('#<span translate="no" class="notranslate">(.*?)</span>#su', '$1', $text);
    }
}
