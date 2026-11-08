<?php

namespace Modules\Transfer\Filament\Pages;

use App\Filament\AdminGroup;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Modules\Invoices\InvoicePdf;
use Modules\Invoices\Models\Invoice;
use Modules\Transfer\Exports\ExportRunner;
use Modules\Transfer\Exports\Exports;
use Modules\Transfer\Exports\ExportWriter;
use Modules\Transfer\Models\ExportTemplate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;
use ZipArchive;

/**
 * Export dát: entita, formát (CSV s oddeľovačom a kódovaním, XLSX, JSON, XML), stĺpce a obdobie.
 * Nastavenie sa dá uložiť ako šablóna a posielať e-mailom; doklady za obdobie ako ZIP s PDF.
 *
 * @property-read Schema $form
 */
class DataExport extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?int $navigationSort = 80;

    protected static ?string $slug = 'export';

    protected string $view = 'transfer::export';

    /** @var array<string, mixed> */
    public array $data = [];

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::System;
    }

    public static function getNavigationLabel(): string
    {
        return __('transfer::admin.export.title');
    }

    public function getTitle(): string
    {
        return __('transfer::admin.export.title');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('transfer.manage') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(['entity' => 'orders', 'format' => 'csv', 'delimiter' => ';', 'encoding' => 'UTF-8', 'columns' => array_keys($this->columns('orders'))]);
    }

    /**
     * @return array<string, string>
     */
    private function columns(?string $entity): array
    {
        $definition = app(Exports::class)->find((string) $entity);

        return $definition ? collect(($definition->columns)())->map(fn ($column) => $column[0])->all() : [];
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('transfer::admin.export.'.$key);

        return $schema->statePath('data')->components([
            Section::make()->columns(3)->schema([
                Select::make('entity')->label($t('entity'))->required()->selectablePlaceholder(false)->live()
                    ->options(collect(app(Exports::class)->all())->map(fn ($definition) => ($definition->label)())->all())
                    ->afterStateUpdated(fn ($state, $set) => $set('columns', array_keys($this->columns($state)))),
                Select::make('format')->label($t('format'))->required()->selectablePlaceholder(false)->live()
                    ->options(['csv' => 'CSV', 'xlsx' => 'XLSX (Excel)', 'json' => 'JSON', 'xml' => 'XML']),
                Select::make('delimiter')->label($t('delimiter'))->selectablePlaceholder(false)
                    ->options([';' => $t('semicolon'), ',' => $t('comma'), 'tab' => $t('tab')])->visible(fn ($get) => $get('format') === 'csv'),
                Select::make('encoding')->label($t('encoding'))->selectablePlaceholder(false)
                    ->options(['UTF-8' => 'UTF-8', 'Windows-1250' => 'Windows-1250'])->visible(fn ($get) => $get('format') === 'csv'),
                DatePicker::make('from')->label($t('from'))->native(false)->visible(fn ($get) => (bool) app(Exports::class)->find((string) $get('entity'))?->hasPeriod),
                DatePicker::make('to')->label($t('to'))->native(false)->visible(fn ($get) => (bool) app(Exports::class)->find((string) $get('entity'))?->hasPeriod),
                CheckboxList::make('columns')->label($t('columns'))->required()->columns(4)->columnSpanFull()->bulkToggleable()
                    ->options(fn ($get) => $this->columns($get('entity'))),
            ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        $t = fn (string $key) => __('transfer::admin.export.'.$key);

        return array_values(array_filter([
            Action::make('download')->label($t('download'))->icon(Heroicon::OutlinedArrowDownTray)->action(fn () => $this->download()),
            Action::make('saveTemplate')->label($t('save_template'))->icon(Heroicon::OutlinedBookmark)->color('gray')
                ->schema([
                    TextInput::make('name')->label($t('template_name'))->required()->maxLength(120),
                    Select::make('schedule')->label($t('schedule'))->selectablePlaceholder(false)->default('none')->live()
                        ->options(collect(['none', 'daily', 'weekly', 'monthly'])->mapWithKeys(fn ($s) => [$s => $t('schedules.'.$s)])->all()),
                    TextInput::make('email')->label($t('email'))->email()->required(fn ($get) => $get('schedule') !== 'none')
                        ->visible(fn ($get) => $get('schedule') !== 'none'),
                ])
                ->action(function (array $data) use ($t): void {
                    ExportTemplate::query()->create([...$data, 'config' => collect($this->form->getState())->except(['from', 'to'])->all()]);
                    Notification::make()->success()->title($t('template_saved'))->send();
                }),
            class_exists(InvoicePdf::class) ? Action::make('invoicesZip')->label($t('invoices_zip'))->icon(Heroicon::OutlinedArchiveBox)->color('gray')
                ->schema([
                    DatePicker::make('from')->label($t('from'))->required()->native(false)->default(now()->subMonthNoOverflow()->startOfMonth()),
                    DatePicker::make('to')->label($t('to'))->required()->native(false)->default(now()->subMonthNoOverflow()->endOfMonth()),
                ])
                ->action(fn (array $data) => $this->invoicesZip($data['from'], $data['to'])) : null,
        ]));
    }

    public function download(?array $config = null): ?BinaryFileResponse
    {
        $file = app(ExportRunner::class)->run($config ?? $this->form->getState());

        return response()->download($file['path'], $file['name'])->deleteFileAfterSend();
    }

    /**
     * @return Collection<int, ExportTemplate>
     */
    public function getTemplatesProperty(): Collection
    {
        return ExportTemplate::query()->orderBy('name')->get();
    }

    public function runTemplate(int $id): ?BinaryFileResponse
    {
        $template = ExportTemplate::query()->findOrFail($id);

        return $this->download($template->config);
    }

    public function deleteTemplate(int $id): void
    {
        ExportTemplate::query()->whereKey($id)->delete();
    }

    public function invoicesZip(string $from, string $to): ?StreamedResponse
    {
        $invoices = Invoice::query()->whereDate('issued_at', '>=', $from)->whereDate('issued_at', '<=', $to)->orderBy('id')->get();

        if ($invoices->isEmpty()) {
            Notification::make()->warning()->title(__('transfer::admin.export.empty'))->send();

            return null;
        }

        $path = tempnam(sys_get_temp_dir(), 'inv');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $pdf = app(InvoicePdf::class);

        foreach ($invoices as $invoice) {
            $zip->addFromString($invoice->number.'.pdf', $pdf->render($invoice));
        }

        $zip->close();

        return response()->streamDownload(function () use ($path): void {
            readfile($path);
            @unlink($path);
        }, 'doklady-'.str_replace('-', '', $from).'-'.str_replace('-', '', $to).'.zip', ['Content-Type' => 'application/zip']);
    }

    public static function formats(): array
    {
        return ExportWriter::FORMATS;
    }
}
