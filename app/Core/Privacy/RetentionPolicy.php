<?php

namespace App\Core\Privacy;

use Closure;
use Illuminate\Support\Carbon;

/**
 * Pravidlo uchovávania osobných údajov: po koľkých dňoch sa údaje zmažú alebo anonymizujú.
 */
final class RetentionPolicy
{
    /**
     * @param  Closure(): string  $label
     * @param  Closure(): string  $description
     * @param  Closure(Carbon): int  $apply  Spracuje záznamy staršie ako daný čas, vráti počet.
     */
    public function __construct(
        public readonly string $key,
        public readonly Closure $label,
        public readonly Closure $description,
        public readonly int $defaultDays,
        public readonly bool $enabledByDefault,
        public readonly Closure $apply,
        public readonly int $minDays = 1,
    ) {}
}
