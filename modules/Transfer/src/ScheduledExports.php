<?php

namespace Modules\Transfer;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Modules\Transfer\Exports\ExportRunner;
use Modules\Transfer\Models\ExportTemplate;
use Throwable;

/**
 * Pošle uložené šablóny exportu, ktorým nastal čas (denne, týždenne, mesačne), ako prílohu e-mailu.
 * Export s obdobím obsahuje predchádzajúci deň, týždeň alebo mesiac.
 */
class ScheduledExports
{
    public function __construct(private readonly ExportRunner $runner) {}

    public function send(): int
    {
        $sent = 0;

        foreach (ExportTemplate::query()->where('schedule', '!=', 'none')->whereNotNull('email')->get() as $template) {
            if (! $template->isDue()) {
                continue;
            }

            [$from, $to] = match ($template->schedule) {
                'daily' => [now()->subDay()->toDateString(), now()->subDay()->toDateString()],
                'weekly' => [now()->subWeek()->startOfWeek()->toDateString(), now()->subWeek()->endOfWeek()->toDateString()],
                default => [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            };

            $template->forceFill(['last_sent_at' => now()])->save();

            try {
                $file = $this->runner->run([...$template->config, 'from' => $from, 'to' => $to]);
                Mail::raw(__('transfer::admin.export.scheduled_body', ['name' => $template->name, 'from' => $from, 'to' => $to]), function (Message $message) use ($template, $file): void {
                    $message->to($template->email)->subject(__('transfer::admin.export.scheduled_subject', ['name' => $template->name]))
                        ->attach($file['path'], ['as' => $file['name']]);
                });
                File::delete($file['path']);
                $sent++;
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $sent;
    }
}
