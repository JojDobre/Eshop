<?php

namespace App\Core\Features;

use Closure;

/**
 * Funkcia obchodu, ktorá sa dá zapnúť a vypnúť v Nastavenia → Funkcie obchodu.
 */
final class FeatureToggle
{
    /**
     * @param  Closure(): string  $label
     * @param  Closure(): string  $description
     * @param  Closure(): bool  $isEnabled
     * @param  Closure(bool): void  $set  Zapne/vypne funkciu (môže vyhodiť výnimku s vysvetlením).
     * @param  (Closure(): ?string)|null  $settingsUrl  Odkaz na podrobné nastavenia.
     */
    public function __construct(
        public readonly string $key,
        public readonly string $group,
        public readonly Closure $label,
        public readonly Closure $description,
        public readonly Closure $isEnabled,
        public readonly Closure $set,
        public readonly ?Closure $settingsUrl = null,
    ) {}
}
