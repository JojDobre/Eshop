<?php

namespace Modules\Returns\Filament\Resources\ReturnRequests\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Modules\Returns\Filament\Resources\ReturnRequests\ReturnRequestResource;
use Modules\Returns\Models\ReturnRequest;
use Modules\Returns\ReturnOutcome;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListReturnRequests extends ListRecords
{
    protected static string $resource = ReturnRequestResource::class;

    protected function getHeaderActions(): array
    {
        // Prehľad žiadostí na export (CSV pre Excel).
        return [
            Action::make('export')->label(__('returns::admin.export'))->icon(Heroicon::OutlinedArrowDownTray)->color('gray')
                ->action(fn (): StreamedResponse => response()->streamDownload(function (): void {
                    $out = fopen('php://output', 'w');
                    fwrite($out, "\xEF\xBB\xBF");
                    $t = fn (string $key) => __('returns::admin.'.$key);
                    fputcsv($out, [$t('number'), $t('type'), $t('order'), $t('customer'), $t('items'), $t('reason'), $t('status'), $t('created_at'), $t('deadline_at'), $t('outcome'), $t('resolved_at')], ';');

                    ReturnRequest::query()->with('order')->orderBy('id')->each(function (ReturnRequest $request) use ($out): void {
                        fputcsv($out, [
                            $request->number, $request->type->getLabel(), $request->order?->number, $request->order?->billing_name,
                            $request->itemsSummary(), $request->reason, $request->status->getLabel(), $request->created_at->format('Y-m-d'),
                            $request->deadline_at->format('Y-m-d'), $request->outcome ? ReturnOutcome::from($request->outcome)->getLabel() : '', $request->resolved_at?->format('Y-m-d'),
                        ], ';');
                    });

                    fclose($out);
                }, 'reklamacie-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8'])),
        ];
    }
}
