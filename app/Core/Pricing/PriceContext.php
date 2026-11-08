<?php

namespace App\Core\Pricing;

use App\Core\Features\FeatureToggles;
use App\Models\CustomerGroup;
use Closure;
use Throwable;

/**
 * Zákaznícka skupina, pre ktorú sa v tejto požiadavke počítajú ceny.
 * Skupinu určuje modul zákazníkov (prihlásený zákazník alebo predvolená skupina);
 * bez neho, alebo keď sú skupiny vypnuté, sa používajú bežné ceny.
 */
class PriceContext
{
    /** @var (Closure(): ?CustomerGroup)|null */
    private static ?Closure $resolver = null;

    private bool $resolved = false;

    private ?CustomerGroup $group = null;

    /**
     * @param  (Closure(): ?CustomerGroup)|null  $resolver
     */
    public static function resolveUsing(?Closure $resolver): void
    {
        self::$resolver = $resolver;
    }

    /** Sú zákaznícke skupiny zapnuté (Nastavenia → Funkcie obchodu)? */
    public static function groupsEnabled(): bool
    {
        $toggle = app(FeatureToggles::class)->find('customers.groups');

        return $toggle !== null && (bool) ($toggle->isEnabled)();
    }

    public function group(): ?CustomerGroup
    {
        if (! $this->resolved) {
            $this->resolved = true;

            try {
                $this->group = self::$resolver !== null ? (self::$resolver)() : null;
            } catch (Throwable) {
                // Napr. pred inštaláciou alebo pred migráciou tabuľky skupín.
                $this->group = null;
            }
        }

        return $this->group;
    }

    public function groupId(): ?int
    {
        return $this->group()?->id;
    }

    /** Majú sa ceny zobrazovať hlavne bez DPH (B2B skupina)? */
    public function showsNetPrices(): bool
    {
        return (bool) $this->group()?->prices_without_vat;
    }

    /** Nastaví skupinu napevno (napr. ručná objednávka v administrácii, testy). */
    public function set(?CustomerGroup $group): void
    {
        $this->group = $group;
        $this->resolved = true;
    }

    public function forget(): void
    {
        $this->resolved = false;
        $this->group = null;
    }
}
