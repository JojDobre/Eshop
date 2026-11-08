<?php

namespace Modules\Orders\Filament;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Modules\Orders\Actions\CreateShipment;
use Modules\Orders\Actions\PrintShipmentLabels;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\Shipment;
use Modules\Orders\OrderStatus;
use Modules\Shipping\Carriers\CarrierException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Akcie zásielok v administrácii: vytvorenie zásielky, štítky a hromadné spracovanie objednávok.
 */
final class ShipmentActions
{
    private static function t(string $key, array $replace = []): string
    {
        return __('orders::admin.shipments.'.$key, $replace);
    }

    /**
     * Vytvorenie zásielky pre jednu objednávku (pri vlastnom kuriérovi so sledovacím číslom).
     *
     * @param  \Closure(): Order  $order
     */
    public static function create(\Closure $order): Action
    {
        return Action::make('createShipment')
            ->label(self::t('create'))
            ->icon('heroicon-o-truck')
            ->color('gray')
            ->visible(fn () => $order()->shippingMethod?->carrier !== null && $order()->status !== OrderStatus::Cancelled)
            ->schema(fn () => [
                TextInput::make('tracking_number')->label(self::t('tracking_number'))->maxLength(64)
                    ->helperText(self::t('tracking_number_help'))
                    ->visible($order()->shippingMethod?->carrier === 'manual'),
                TextInput::make('weight')->label(self::t('weight'))->numeric()->minValue(0.01)->suffix('kg')
                    ->helperText(self::t('weight_help'))
                    ->visible($order()->shippingMethod?->carrier !== 'manual'),
            ])
            ->action(function (array $data, CreateShipment $create) use ($order): void {
                try {
                    $shipment = $create->handle(
                        $order(),
                        $data['tracking_number'] ?? null,
                        filled($data['weight'] ?? null) ? (int) round((float) $data['weight'] * 1000) : null,
                        self::user(),
                    );
                    Notification::make()->success()->title(self::t('created', ['number' => $shipment->tracking_number ?? $shipment->carrier_reference]))->send();
                } catch (CarrierException $e) {
                    Notification::make()->danger()->title(self::t('failed'))->body($e->getMessage())->persistent()->send();
                }
            });
    }

    /**
     * Stiahnutie štítkov zásielok objednávky.
     *
     * @param  \Closure(): Order  $order
     */
    public static function labels(\Closure $order): Action
    {
        return Action::make('shipmentLabels')
            ->label(self::t('labels'))
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->visible(fn () => $order()->shipments()->where('carrier', '!=', 'manual')->exists())
            ->action(fn (PrintShipmentLabels $print) => self::download($print, $order()->shipments()->where('carrier', '!=', 'manual')->get()));
    }

    /**
     * Hromadne: vytvorí chýbajúce zásielky vybraných objednávok a stiahne štítky naraz.
     */
    public static function bulk(): BulkAction
    {
        return BulkAction::make('shipAndPrint')
            ->label(self::t('bulk'))
            ->icon('heroicon-o-printer')
            ->requiresConfirmation()
            ->modalDescription(self::t('bulk_help'))
            ->action(function (Collection $records, CreateShipment $create, PrintShipmentLabels $print) {
                $errors = [];
                $shipments = collect();

                foreach ($records as $order) {
                    /** @var Order $order */
                    if ($order->shippingMethod?->carrier === null || $order->shippingMethod->carrier === 'manual' || $order->status === OrderStatus::Cancelled) {
                        continue;
                    }

                    try {
                        $shipments->push($order->shipments()->first() ?? $create->handle($order, user: self::user()));
                    } catch (Throwable $e) {
                        $errors[] = $order->number.': '.$e->getMessage();
                    }
                }

                foreach ($errors as $error) {
                    Notification::make()->danger()->title(self::t('failed'))->body($error)->persistent()->send();
                }

                if ($shipments->isEmpty()) {
                    Notification::make()->warning()->title(self::t('nothing_to_print'))->send();

                    return null;
                }

                return self::download($print, $shipments);
            })
            ->deselectRecordsAfterCompletion();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Shipment>  $shipments
     */
    private static function download(PrintShipmentLabels $print, \Illuminate\Support\Collection $shipments): ?BinaryFileResponse
    {
        try {
            $file = $print->handle($shipments);

            return response()->download($file['path'], $file['name'])->deleteFileAfterSend();
        } catch (CarrierException $e) {
            Notification::make()->danger()->title(self::t('labels_failed'))->body($e->getMessage())->persistent()->send();

            return null;
        }
    }

    private static function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
