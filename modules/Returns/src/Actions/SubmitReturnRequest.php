<?php

namespace Modules\Returns\Actions;

use App\Core\Mail\TemplateMail;
use App\Settings\GeneralSettings;
use App\Settings\MailSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Orders\Models\Order;
use Modules\Orders\NumberSequence;
use Modules\Returns\ComplaintResolution;
use Modules\Returns\Models\ReturnRequest;
use Modules\Returns\ReturnMailVariables;
use Modules\Returns\ReturnStatus;
use Modules\Returns\ReturnType;
use Throwable;

/**
 * Prijme odstúpenie od zmluvy alebo reklamáciu a bez zbytočného odkladu ju potvrdí zákazníkovi e-mailom.
 */
class SubmitReturnRequest
{
    public function __construct(private readonly NumberSequence $numbers) {}

    /**
     * @param  array<int, int>  $quantities  order_item_id => počet kusov
     */
    public function handle(Order $order, ReturnType $type, array $quantities, ?string $reason, ?ComplaintResolution $resolution, ?string $refundAccount, ?string $ipAddress = null): ReturnRequest
    {
        $order->loadMissing('items');
        $items = [];

        foreach ($order->items as $item) {
            $quantity = min((int) ($quantities[$item->id] ?? 0), $item->quantity);

            if ($quantity > 0) {
                $items[] = ['order_item_id' => $item->id, 'name' => $item->fullName(), 'quantity' => $quantity];
            }
        }

        if ($items === []) {
            throw new \InvalidArgumentException('No items selected.');
        }

        $request = DB::transaction(function () use ($order, $type, $items, $reason, $resolution, $refundAccount, $ipAddress): ReturnRequest {
            $year = now()->format('Y');

            return ReturnRequest::query()->create([
                'order_id' => $order->id,
                'number' => 'R'.$year.str_pad((string) $this->numbers->next('returns.'.$year), 5, '0', STR_PAD_LEFT),
                'type' => $type,
                'status' => ReturnStatus::New,
                'items' => $items,
                'reason' => filled($reason) ? trim($reason) : null,
                'preferred_resolution' => $type === ReturnType::Complaint ? $resolution : null,
                'refund_account' => filled($refundAccount) ? strtoupper((string) preg_replace('/\s+/', '', $refundAccount)) : null,
                'deadline_at' => now()->addDays($type->deadlineDays())->endOfDay(),
                'ip_address' => $ipAddress,
            ]);
        });

        $request->setRelation('order', $order);
        $variables = ReturnMailVariables::for($request);

        try {
            Mail::to($order->email, $order->billing_name)->locale($order->locale)
                ->send(new TemplateMail('returns.received', $variables, $order->locale, ReturnMailVariables::RAW));
        } catch (Throwable $e) {
            Log::error('Return confirmation e-mail failed.', ['request' => $request->number, 'exception' => $e]);
        }

        try {
            Mail::to(app(MailSettings::class)->notificationAddress(app(GeneralSettings::class)))
                ->send(new TemplateMail('returns.notification', $variables, null, ReturnMailVariables::RAW));
        } catch (Throwable $e) {
            Log::error('Return notification e-mail failed.', ['request' => $request->number, 'exception' => $e]);
        }

        return $request;
    }
}
