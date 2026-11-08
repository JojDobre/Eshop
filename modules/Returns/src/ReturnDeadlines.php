<?php

namespace Modules\Returns;

use App\Core\Mail\TemplateMail;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Mail;
use Modules\Returns\Models\ReturnRequest;
use Throwable;

/**
 * Upozornenie obchodníka na nevybavené žiadosti, ktorým sa blíži zákonná lehota (webový cron, denne).
 */
class ReturnDeadlines
{
    public const WARN_DAYS = 3;

    public function warn(): int
    {
        $requests = ReturnRequest::query()->whereNull('resolved_at')->whereNull('deadline_warned_at')
            ->where('deadline_at', '<=', now()->addDays(self::WARN_DAYS)->endOfDay())
            ->with('order')->orderBy('deadline_at')->get();
        $email = app(GeneralSettings::class)->contact_email;

        if ($requests->isEmpty() || blank($email)) {
            return 0;
        }

        try {
            Mail::to($email)->send(new TemplateMail('returns.deadline_warning', [
                'requests' => $requests->map(fn (ReturnRequest $request) => '- '.$request->number.' ('.$request->type->getLabel().', '.$request->order?->number.') – '
                    .__('returns::admin.deadline_until', ['date' => $request->deadline_at->format('j. n. Y')]))->implode("\n"),
                'admin_url' => url('/admin/return-requests'),
            ], null, ['requests', 'admin_url']));
        } catch (Throwable $e) {
            report($e);

            return 0;
        }

        ReturnRequest::query()->whereKey($requests->modelKeys())->update(['deadline_warned_at' => now()]);

        return $requests->count();
    }
}
