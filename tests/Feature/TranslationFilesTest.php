<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Jazykové súbory jadra, modulov a šablón majú v sk, cs aj en rovnaké kľúče a žiadny text nie je prázdny.
 * Chýbajúci preklad by sa v obchode zobrazil ako technický kľúč (napr. „theme::shop.cart“).
 */
class TranslationFilesTest extends TestCase
{
    private const LOCALES = ['sk', 'cs', 'en'];

    public function test_all_language_files_have_the_same_keys_in_every_language(): void
    {
        $problems = [];
        $directories = [base_path('lang'), ...glob(base_path('modules/*/lang'), GLOB_ONLYDIR), ...glob(base_path('themes/*/lang'), GLOB_ONLYDIR)];

        foreach ($directories as $directory) {
            $files = collect(self::LOCALES)->flatMap(fn (string $locale) => array_map('basename', glob($directory.'/'.$locale.'/*.php')))->unique();

            foreach ($files as $file) {
                $keys = [];

                foreach (self::LOCALES as $locale) {
                    $path = $directory.'/'.$locale.'/'.$file;

                    if (! is_file($path)) {
                        $problems[] = 'chýba súbor '.str_replace(base_path().'/', '', $path);

                        continue;
                    }

                    $keys[$locale] = $this->flatten(require $path);

                    foreach ($keys[$locale] as $key => $value) {
                        if (is_string($value) && trim($value) === '') {
                            $problems[] = 'prázdny text '.str_replace(base_path().'/', '', $path).': '.$key;
                        }
                    }
                }

                foreach ($keys as $locale => $localeKeys) {
                    foreach ($keys as $otherLocale => $otherKeys) {
                        foreach (array_keys(array_diff_key($localeKeys, $otherKeys)) as $key) {
                            // Anglické názvy polí vo validácii netreba (Laravel použije názov poľa).
                            if ($otherLocale === 'en' && $file === 'validation.php' && str_starts_with($key, 'attributes.')) {
                                continue;
                            }

                            $problems[] = 'chýba '.str_replace(base_path().'/', '', $directory).'/'.$otherLocale.'/'.$file.': '.$key;
                        }
                    }
                }
            }
        }

        $this->assertSame([], array_values(array_unique($problems)), implode("\n", array_unique($problems)));
    }

    public function test_theme_and_module_settings_have_labels_in_every_language(): void
    {
        $problems = [];

        foreach ([...glob(base_path('themes/*/theme.json')), ...glob(base_path('modules/*/module.json'))] as $file) {
            $this->collectLabelProblems(json_decode((string) file_get_contents($file), true) ?? [], str_replace(base_path().'/', '', $file), $problems);
        }

        $this->assertSame([], $problems, implode("\n", $problems));
    }

    /**
     * Texty v tvare {"sk": …, "cs": …, "en": …} musia mať všetky tri jazyky a nesmú byť prázdne.
     *
     * @param  array<mixed>  $node
     * @param  list<string>  $problems
     */
    private function collectLabelProblems(array $node, string $path, array &$problems): void
    {
        $keys = array_keys($node);

        if ($keys !== [] && array_diff($keys, self::LOCALES) === [] && ! array_is_list($node)) {
            foreach (self::LOCALES as $locale) {
                if (! isset($node[$locale]) || (is_string($node[$locale]) && trim($node[$locale]) === '')) {
                    $problems[] = $path.': chýba '.$locale;
                }
            }

            return;
        }

        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $this->collectLabelProblems($value, $path.'.'.$key, $problems);
            }
        }
    }

    /**
     * @param  array<mixed>  $values
     * @return array<string, mixed>
     */
    private function flatten(array $values, string $prefix = ''): array
    {
        $flat = [];

        foreach ($values as $key => $value) {
            is_array($value) && $value !== []
                ? $flat += $this->flatten($value, $prefix.$key.'.')
                : $flat[$prefix.$key] = $value;
        }

        return $flat;
    }
}
