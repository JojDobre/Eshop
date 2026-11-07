<?php

namespace App\Core\Mail;

use App\Models\EmailTemplate;
use App\Settings\GeneralSettings;
use Throwable;

/**
 * Upraviteľné e-maily: definície od modulov a ich texty (upravené v administrácii, inak predvolené).
 */
class EmailTemplates
{
    /** @var array<string, EmailTemplateDefinition> */
    private array $definitions = [];

    public function register(EmailTemplateDefinition $definition): void
    {
        $this->definitions[$definition->key] = $definition;
    }

    /**
     * @return array<string, EmailTemplateDefinition>
     */
    public function definitions(): array
    {
        return $this->definitions;
    }

    public function definition(string $key): ?EmailTemplateDefinition
    {
        return $this->definitions[$key] ?? null;
    }

    public function isEnabled(string $key): bool
    {
        return $this->stored($key)?->is_active ?? true;
    }

    /**
     * Predmet a telo e-mailu s doplnenými premennými.
     *
     * @param  array<string, string>  $variables  Hodnoty premenných (bez zátvoriek). Hodnoty v Markdowne
     *                                            (napr. tabuľka položiek) sa nevkladajú escapované.
     * @param  list<string>  $rawVariables  Premenné, ktorých hodnota je hotový Markdown.
     * @return array{subject: string, body: string}
     */
    public function render(string $key, array $variables, ?string $locale = null, array $rawVariables = []): array
    {
        $definition = $this->definitions[$key] ?? throw new \InvalidArgumentException("Unknown e-mail template [{$key}].");
        $stored = $this->stored($key);
        $locale ??= app()->getLocale();

        $subject = ($stored ? $stored->getTranslation('subject', $locale, false) : null) ?: $definition->defaultSubject($locale);
        $body = ($stored ? $stored->getTranslation('body', $locale, false) : null) ?: $definition->defaultBody($locale);

        return $this->fill($subject, $body, $variables, $rawVariables);
    }

    /**
     * Doplní premenné do zadaného predmetu a tela (aj pre náhľad neuloženého textu v administrácii).
     *
     * @param  array<string, string>  $variables
     * @param  list<string>  $rawVariables
     * @return array{subject: string, body: string}
     */
    public function fill(string $subject, string $body, array $variables, array $rawVariables = []): array
    {
        $variables = ['shop_name' => $this->shopName(), ...$variables];

        $replace = function (string $text, bool $markdown) use ($variables, $rawVariables): string {
            return (string) preg_replace_callback('/\{([a-z_]+)\}/', function (array $match) use ($variables, $rawVariables, $markdown): string {
                if (! array_key_exists($match[1], $variables)) {
                    return $match[0];
                }

                $value = (string) $variables[$match[1]];

                return $markdown && ! in_array($match[1], $rawVariables, true) ? e($value) : $value;
            }, $text);
        };

        return [
            'subject' => strip_tags($replace($subject, false)),
            'body' => $replace($body, true),
        ];
    }

    /**
     * Zabezpečí záznam pre každý registrovaný e-mail (zoznam v administrácii pracuje so záznamami).
     */
    public function syncRecords(): void
    {
        $existing = EmailTemplate::query()->pluck('key')->all();

        foreach (array_diff(array_keys($this->definitions), $existing) as $key) {
            EmailTemplate::query()->create(['key' => $key, 'is_active' => true]);
        }
    }

    private function stored(string $key): ?EmailTemplate
    {
        try {
            return EmailTemplate::query()->where('key', $key)->first();
        } catch (Throwable) {
            return null;
        }
    }

    private function shopName(): string
    {
        try {
            return app(GeneralSettings::class)->shop_name;
        } catch (Throwable) {
            return (string) config('app.name');
        }
    }
}
