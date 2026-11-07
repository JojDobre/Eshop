<?php

namespace Modules\Translator\Providers;

use Illuminate\Support\Facades\Http;

/**
 * DeepL API (bezplatné kľúče končia na „:fx“ a používajú api-free.deepl.com).
 */
class DeepLProvider implements TranslationProvider
{
    public function __construct(private readonly string $key) {}

    public function translate(array $texts, string $from, string $to): array
    {
        $host = str_ends_with($this->key, ':fx') ? 'https://api-free.deepl.com' : 'https://api.deepl.com';

        $response = Http::timeout(60)
            ->withHeaders(['Authorization' => 'DeepL-Auth-Key '.$this->key])
            ->asJson()
            ->post($host.'/v2/translate', [
                'text' => array_values($texts),
                'source_lang' => strtoupper($from),
                'target_lang' => $this->target($to),
                'tag_handling' => 'html',
                // Pojmy zo slovníka sú obalené značkou, ktorú DeepL neprekladá.
                'ignore_tags' => ['x-keep'],
            ])
            ->throw();

        return array_map(fn (array $item) => (string) $item['text'], $response->json('translations', []));
    }

    private function target(string $locale): string
    {
        // DeepL vyžaduje pri angličtine variant.
        return match ($locale) {
            'en' => 'EN-GB',
            'pt' => 'PT-PT',
            default => strtoupper($locale),
        };
    }

    public function protect(string $term): string
    {
        return '<x-keep>'.$term.'</x-keep>';
    }

    public function unprotect(string $text): string
    {
        return str_replace(['<x-keep>', '</x-keep>'], '', $text);
    }
}
