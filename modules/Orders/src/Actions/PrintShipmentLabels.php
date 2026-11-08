<?php

namespace Modules\Orders\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Modules\Orders\Models\Shipment;
use Modules\Orders\Shipping\CarrierHealth;
use Modules\Shipping\Carriers\CarrierException;
use Throwable;
use ZipArchive;

/**
 * Štítky zásielok na tlač naraz: jeden kuriér = jedno PDF, viac kuriérov = ZIP s PDF pre každého.
 */
class PrintShipmentLabels
{
    public function __construct(private readonly CarrierHealth $health) {}

    /**
     * @param  Collection<int, Shipment>  $shipments
     * @return array{path: string, name: string}
     *
     * @throws CarrierException
     */
    public function handle(Collection $shipments): array
    {
        $files = [];

        foreach ($shipments->groupBy('carrier') as $carrierKey => $group) {
            $carrier = $group->first()->carrierService();

            if ($carrier === null) {
                continue;
            }

            // Jedna zásielka so štítkom uloženým pri vytvorení – netreba volať kuriéra.
            if ($group->count() === 1 && $group->first()->label_path && Storage::disk(CreateShipment::DISK)->exists($group->first()->label_path)) {
                $files[$carrierKey] = Storage::disk(CreateShipment::DISK)->get($group->first()->label_path);

                continue;
            }

            try {
                $files[$carrierKey] = $carrier->labels($group->pluck('carrier_reference')->filter()->values()->all());
                $this->health->success($carrierKey);
            } catch (Throwable $e) {
                $this->health->failure($carrierKey, $e);

                throw $e instanceof CarrierException ? $e : new CarrierException($e->getMessage(), CarrierException::UNAVAILABLE, $e);
            }
        }

        if ($files === []) {
            throw new CarrierException(__('orders::admin.shipments.no_labels'));
        }

        $directory = storage_path('app/private/labels/print');
        @mkdir($directory, 0775, true);
        $stamp = now()->format('Ymd-His');

        if (count($files) === 1) {
            $path = $directory.'/labels-'.$stamp.'-'.bin2hex(random_bytes(3)).'.pdf';
            file_put_contents($path, reset($files));

            return ['path' => $path, 'name' => 'stitky-'.array_key_first($files).'-'.$stamp.'.pdf'];
        }

        $path = $directory.'/labels-'.$stamp.'-'.bin2hex(random_bytes(3)).'.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $carrierKey => $pdf) {
            $zip->addFromString('stitky-'.$carrierKey.'.pdf', $pdf);
        }

        $zip->close();

        return ['path' => $path, 'name' => 'stitky-'.$stamp.'.zip'];
    }
}
