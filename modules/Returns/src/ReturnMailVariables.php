<?php

namespace Modules\Returns;

use Modules\Returns\Models\ReturnRequest;

/**
 * Premenné pre e-maily o vrátení a reklamácii.
 */
final class ReturnMailVariables
{
    public const RAW = ['order_url', 'admin_url'];

    /**
     * @return array<string, string>
     */
    public static function for(ReturnRequest $request): array
    {
        $order = $request->order;

        return [
            'customer_name' => $order->billing_name,
            'request_number' => $request->number,
            'request_type' => $request->type->getLabel(),
            'request_status' => $request->status->getLabel(),
            'request_items' => $request->itemsSummary(),
            'reason' => (string) $request->reason,
            'resolution' => (string) $request->resolution,
            'deadline' => $request->deadline_at->format('j. n. Y'),
            'order_number' => $order->number,
            'order_url' => $order->publicUrl(),
            'admin_url' => url('/admin/returns/'.$request->id),
        ];
    }
}
