<?php

namespace Modules\Accounting\Filament\Pages;

use App\Filament\AdminGroup;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Modules\Accounting\AccountingExporters;
use Modules\Invoices\InvoiceType;
use Modules\Invoices\Models\Invoice;
use Modules\Orders\Models\Order;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Export dokladov za obdobie do účtovného programu (Pohoda, Money S3).
 *
 * @property-read Schema $form
 */
class AccountingExport extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?int $navigationSort = 70;

    protected static ?string $slug = 'accounting-export';

    protected string $view = 'accounting::export';

    /** @var array<string, mixed> */
    public array $data = [];

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Sales;
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::admin.title');
    }

    public function getTitle(): string
    {
        return __('accounting::admin.title');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('accounting.export') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'format' => 'pohoda',
            'from' => now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
            'to' => now()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            'documents' => ['invoices', 'credit_notes'],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('accounting::admin.'.$key);

        return $schema->statePath('data')->components([
            Section::make()->description($t('help'))->columns(2)->schema([
                Select::make('format')->label($t('format'))->required()->selectablePlaceholder(false)->live()
                    ->options(collect(app(AccountingExporters::class)->all())->map(fn ($exporter) => $exporter->label())->all()),
                CheckboxList::make('documents')->label($t('documents'))->required()
                    ->options(fn ($get) => array_filter([
                        'invoices' => $t('invoices'),
                        'credit_notes' => $t('credit_notes'),
                        'orders' => app(AccountingExporters::class)->find((string) $get('format'))?->supportsOrders() ? $t('orders') : null,
                    ])),
                DatePicker::make('from')->label($t('from'))->required()->native(false),
                DatePicker::make('to')->label($t('to'))->required()->native(false)->afterOrEqual('from'),
            ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')->label(__('accounting::admin.download'))->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn () => $this->download()),
        ];
    }

    public function download(): ?StreamedResponse
    {
        $data = $this->form->getState();
        $exporter = app(AccountingExporters::class)->find($data['format']);
        $from = Carbon::parse($data['from'])->startOfDay();
        $to = Carbon::parse($data['to'])->endOfDay();
        $documents = (array) $data['documents'];

        $types = array_values(array_filter([
            in_array('invoices', $documents, true) ? InvoiceType::Invoice : null,
            in_array('credit_notes', $documents, true) ? InvoiceType::CreditNote : null,
        ]));

        $invoices = $types === [] ? collect() : Invoice::query()->with('relatedInvoice')->whereIn('type', $types)
            ->whereDate('issued_at', '>=', $from->toDateString())->whereDate('issued_at', '<=', $to->toDateString())->orderBy('issued_at')->orderBy('id')->get();
        $orders = $exporter->supportsOrders() && in_array('orders', $documents, true)
            ? Order::query()->with('items')->whereBetween('created_at', [$from, $to])->orderBy('id')->get()
            : collect();

        if ($invoices->isEmpty() && $orders->isEmpty()) {
            Notification::make()->warning()->title(__('accounting::admin.empty'))->send();

            return null;
        }

        $xml = $exporter->export($invoices, $orders);
        $name = $exporter->key().'-'.$from->format('Ymd').'-'.$to->format('Ymd').'.xml';

        return response()->streamDownload(fn () => print ($xml), $name, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
