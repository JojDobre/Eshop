<?php

namespace Modules\Shipping\Filament\Pages;

use App\Filament\Pages\Settings\BaseSettingsPage;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Modules\Shipping\Carriers\CarrierRegistry;
use Modules\Shipping\Carriers\ShippingCarrier;
use Modules\Shipping\Models\PickupPoint;
use Throwable;

/**
 * Nastavenia kuriéra: prístupové údaje, tlačidlo „Otestovať spojenie“ a aktualizácia výdajných miest.
 */
abstract class CarrierSettingsPage extends BaseSettingsPage
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    abstract protected static function carrierKey(): string;

    public static function getNavigationLabel(): string
    {
        return self::carrier()?->label() ?? static::carrierKey();
    }

    public function getTitle(): string
    {
        return static::getNavigationLabel();
    }

    protected static function carrier(): ?ShippingCarrier
    {
        return app(CarrierRegistry::class)->find(static::carrierKey());
    }

    protected function getHeaderActions(): array
    {
        $t = fn (string $key, array $replace = []) => __('shipping::admin.carriers.'.$key, $replace);

        return [
            Action::make('test')->label($t('test'))->icon(Heroicon::OutlinedSignal)->color('gray')
                ->action(function () use ($t): void {
                    try {
                        self::carrier()?->testConnection();
                        Notification::make()->success()->title($t('test_ok'))->send();
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title($t('test_failed'))->body($e->getMessage())->persistent()->send();
                    }
                }),
            Action::make('sync')->label($t('sync'))->icon(Heroicon::OutlinedArrowPath)->color('gray')
                ->visible(fn () => static::hasPickupPointList())
                ->action(function () use ($t): void {
                    try {
                        $count = (int) self::carrier()?->syncPickupPoints();
                        Notification::make()->success()->title($t('synced', ['count' => $count]))->send();
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title($t('test_failed'))->body($e->getMessage())->persistent()->send();
                    }
                }),
        ];
    }

    /** Používa kuriér zoznam výdajných miest v obchode? */
    protected static function hasPickupPointList(): bool
    {
        return false;
    }

    /** Počet uložených výdajných miest (na zobrazenie v nastaveniach). */
    protected static function pickupPointCount(): int
    {
        return PickupPoint::query()->where('carrier', static::carrierKey())->where('is_active', true)->count();
    }
}
