<?php

namespace Modules\Translator;

use App\Core\Content\MachineTranslator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Translator\Models\MachineTranslation;
use Modules\Translator\Providers\DeepLProvider;
use Modules\Translator\Providers\GoogleProvider;
use Modules\Translator\Providers\TranslationProvider;
use RuntimeException;

/**
 * Strojový preklad obsahu so slovníkom pojmov, mesačným limitom a označením „na kontrolu“.
 */
class MachineTranslationService implements MachineTranslator
{
    /** Stĺpce, ktoré sa neprekladajú (adresa sa vytvorí z preloženého názvu). */
    private const SKIPPED = ['slug'];

    private ?TranslationProvider $provider = null;

    public function isConfigured(): bool
    {
        $settings = app(TranslatorSettings::class);

        return in_array($settings->provider, ['deepl', 'google'], true) && filled($settings->api_key);
    }

    public function useProvider(TranslationProvider $provider): void
    {
        $this->provider = $provider;
    }

    public function translateModel(Model $record, string $from, string $to): int
    {
        $sources = $this->sources($record, $from);

        if ($sources === []) {
            return 0;
        }

        $translated = $this->translate(array_values($sources), $from, $to);
        $characters = $this->count($sources);

        DB::transaction(function () use ($record, $sources, $translated, $to, $characters): void {
            foreach (array_keys($sources) as $index => $attribute) {
                $record->setTranslation($attribute, $to, $translated[$index] ?? '');
            }

            // Adresa sa vytvorí z preloženého názvu (HasLocalizedSlug).
            if (in_array('slug', $record->getTranslatableAttributes(), true)) {
                $record->forgetTranslation('slug', $to);
            }

            $record->save();

            MachineTranslation::query()->updateOrCreate(
                ['translatable_type' => $record->getMorphClass(), 'translatable_id' => $record->getKey(), 'locale' => $to],
                ['characters' => $characters, 'reviewed_at' => null],
            );
        });

        return $characters;
    }

    public function needsReview(Model $record, string $locale): bool
    {
        return MachineTranslation::query()
            ->where('translatable_type', $record->getMorphClass())->where('translatable_id', $record->getKey())
            ->where('locale', $locale)->whereNull('reviewed_at')->exists();
    }

    public function approve(Model $record, string $locale): void
    {
        MachineTranslation::query()
            ->where('translatable_type', $record->getMorphClass())->where('translatable_id', $record->getKey())
            ->where('locale', $locale)->update(['reviewed_at' => now()]);
    }

    /**
     * Počet znakov na preklad (pre odhad pred hromadným prekladom).
     */
    public function estimate(Model $record, string $from): int
    {
        return $this->count($this->sources($record, $from));
    }

    /**
     * Preklad textov so slovníkom a kontrolou limitu.
     *
     * @param  list<string>  $texts
     * @return list<string>
     */
    public function translate(array $texts, string $from, string $to): array
    {
        $characters = $this->count($texts);
        $this->ensureWithinLimit($characters);

        $provider = $this->provider();
        $glossary = $this->glossaryFor($to);
        $prepared = array_map(fn (string $text) => $this->applyGlossary($text, $glossary, $provider), $texts);

        $results = [];

        foreach (array_chunk($prepared, 40, true) as $chunk) {
            $translated = $provider->translate(array_values($chunk), $from, $to);

            foreach (array_keys($chunk) as $position => $index) {
                $text = $provider->unprotect($translated[$position] ?? '');
                // Texty bez HTML (názvy) sa vrátia bez HTML entít.
                $results[$index] = strip_tags($texts[$index]) === $texts[$index]
                    ? html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')
                    : $text;
            }
        }

        $this->recordUsage($characters);
        ksort($results);

        return array_values($results);
    }

    /**
     * Zostávajúce znaky v mesiaci (null = bez limitu).
     */
    public function remaining(): ?int
    {
        $settings = app(TranslatorSettings::class);

        if ($settings->monthly_limit <= 0) {
            return null;
        }

        $used = $settings->usage_month === now()->format('Y-m') ? $settings->used_characters : 0;

        return max(0, $settings->monthly_limit - $used);
    }

    /**
     * @return array<string, string> stĺpec => text v zdrojovom jazyku
     */
    private function sources(Model $record, string $from): array
    {
        $sources = [];

        foreach ($record->getTranslatableAttributes() as $attribute) {
            if (in_array($attribute, self::SKIPPED, true)) {
                continue;
            }

            $value = $record->getTranslation($attribute, $from, false);

            if (is_string($value) && trim(strip_tags($value)) !== '') {
                $sources[$attribute] = $value;
            }
        }

        return $sources;
    }

    /**
     * @param  array<string|int, string>  $texts
     */
    private function count(array $texts): int
    {
        return array_sum(array_map(fn (string $text) => mb_strlen(strip_tags($text)), $texts));
    }

    private function ensureWithinLimit(int $characters): void
    {
        $remaining = $this->remaining();

        if ($remaining !== null && $characters > $remaining) {
            throw new TranslationLimitExceeded(__('translator::admin.limit_exceeded', ['remaining' => $remaining, 'needed' => $characters]));
        }
    }

    private function recordUsage(int $characters): void
    {
        $settings = app(TranslatorSettings::class);
        $month = now()->format('Y-m');

        $settings->used_characters = ($settings->usage_month === $month ? $settings->used_characters : 0) + $characters;
        $settings->usage_month = $month;
        $settings->save();
    }

    /**
     * @return array<string, ?string> pojem => preklad (null = neprekladať)
     */
    private function glossaryFor(string $locale): array
    {
        $terms = [];

        foreach (app(TranslatorSettings::class)->glossary as $entry) {
            $term = trim((string) ($entry['term'] ?? ''));

            if ($term !== '') {
                $translation = trim((string) ($entry['translations'][$locale] ?? ''));
                $terms[$term] = $translation !== '' ? $translation : null;
            }
        }

        // Dlhšie pojmy prvé (aby „Káva Arabica“ mala prednosť pred „Káva“).
        uksort($terms, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return $terms;
    }

    /**
     * @param  array<string, ?string>  $glossary
     */
    private function applyGlossary(string $text, array $glossary, TranslationProvider $provider): string
    {
        foreach ($glossary as $term => $translation) {
            $text = (string) preg_replace_callback(
                '/(?<![\p{L}\p{N}])'.preg_quote($term, '/').'(?![\p{L}\p{N}])/u',
                fn () => $provider->protect(htmlspecialchars($translation ?? $term, ENT_NOQUOTES)),
                $text,
            );
        }

        return $text;
    }

    private function provider(): TranslationProvider
    {
        if ($this->provider !== null) {
            return $this->provider;
        }

        $settings = app(TranslatorSettings::class);

        return match ($settings->provider) {
            'deepl' => new DeepLProvider((string) $settings->api_key),
            'google' => new GoogleProvider((string) $settings->api_key),
            default => throw new RuntimeException(__('translator::admin.not_configured')),
        };
    }
}
