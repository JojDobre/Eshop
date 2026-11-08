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
     * @param  array<string, bool>  $sizes  Predvoľba podľa veľkosti obchodu (small/medium/large).
     *                                      Chýbajúca veľkosť = funkcia je voliteľná, veľkosť ju nemení.
     */
    public function __construct(
        public readonly string $key,
        public readonly string $group,
        public readonly Closure $label,
        public readonly Closure $description,
        public readonly Closure $isEnabled,
        public readonly Closure $set,
        public readonly ?Closure $settingsUrl = null,
        public readonly array $sizes = [],
    ) {}
}
