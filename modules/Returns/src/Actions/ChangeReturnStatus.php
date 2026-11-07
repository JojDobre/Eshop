<?php

namespace Modules\Returns\Actions;

use App\Core\Mail\EmailTemplates;
use App\Core\Mail\TemplateMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Returns\Models\ReturnRequest;
use Modules\Returns\ReturnMailVariables;
use Modules\Returns\ReturnStatus;
use Throwable;

class ChangeReturnStatus
{
    public function handle(ReturnRequest $request, ReturnStatus $status, ?string $resolution, bool $notifyCustomer = true): void
    {
        $request->update([
            'status' => $status,
            'resolution' => filled($resolution) ? trim($resolution) : $request->resolution,
            'resolved_at' => $status->isClosed() ? ($request->resolved_at ?? now()) : null,
        ]);

        if (! $notifyCustomer || ! app(EmailTemplates::class)->isEnabled('returns.status')) {
            return;
        }

        $order = $request->order;

        try {
            Mail::to($order->email, $order->billing_name)->locale($order->locale)
                ->send(new TemplateMail('returns.status', ReturnMailVariables::for($request), $order->locale, ReturnMailVariables::RAW));
        } catch (Throwable $e) {
            Log::error('Return status e-mail failed.', ['request' => $request->number, 'exception' => $e]);
        }
    }
}
