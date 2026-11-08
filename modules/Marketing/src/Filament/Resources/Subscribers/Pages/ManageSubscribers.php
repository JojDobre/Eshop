<?php

namespace Modules\Marketing\Filament\Resources\Subscribers\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Modules\Marketing\Filament\Resources\Subscribers\SubscriberResource;
use Modules\Marketing\Models\Subscriber;
use Modules\Marketing\Newsletter\Newsletter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ManageSubscribers extends ManageRecords
{
    protected static string $resource = SubscriberResource::class;

    protected function getHeaderActions(): array
    {
        $t = fn (string $key, array $replace = []) => __('marketing::admin.subscribers.'.$key, $replace);

        return [
            // Export prihlásených odberateľov do CSV (UTF-8 s BOM pre Excel).
            Action::make('export')->label($t('export'))->icon(Heroicon::OutlinedArrowDownTray)->color('gray')
                ->action(fn (): StreamedResponse => response()->streamDownload(function (): void {
                    $out = fopen('php://output', 'w');
                    fwrite($out, "\xEF\xBB\xBF");
                    fputcsv($out, ['email', 'name', 'locale', 'source', 'confirmed_at'], ';');

                    Subscriber::query()->subscribed()->orderBy('id')->each(function (Subscriber $subscriber) use ($out): void {
                        fputcsv($out, [$subscriber->email, $subscriber->name, $subscriber->locale, $subscriber->source, $subscriber->confirmed_at?->toDateTimeString()], ';');
                    });

                    fclose($out);
                }, 'newsletter-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8'])),
            Action::make('sync_all')->label($t('sync_all'))->icon(Heroicon::OutlinedArrowPath)->color('gray')
                ->visible(fn () => app(Newsletter::class)->provider() !== null)
                ->requiresConfirmation()
                ->action(function () use ($t): void {
                    $newsletter = app(Newsletter::class);
                    $count = 0;

                    try {
                        Subscriber::query()->whereIn('status', [Subscriber::SUBSCRIBED, Subscriber::UNSUBSCRIBED])->orderBy('id')
                            ->each(function (Subscriber $subscriber) use ($newsletter, &$count): void {
                                $count += $newsletter->sync($subscriber, throw: true) ? 1 : 0;
                            });
                        Notification::make()->success()->title($t('synced', ['count' => $count]))->send();
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title($t('sync_failed'))->body($e->getMessage())->persistent()->send();
                    }
                }),
        ];
    }
}
