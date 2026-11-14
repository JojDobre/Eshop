<?php

namespace Modules\Epr\Filament\Pages;

use App\Filament\AdminGroup;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Modules\Epr\EprSettings;
use Modules\Epr\Packaging;
use Modules\Orders\Filament\Resources\Orders\OrderResource;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Výkaz obalov za obdobie (štvrťrok, rok) pre organizáciu zodpovednosti výrobcov.
 *
 * @property-read Schema $form
 */
class EprReport extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?int $navigationSort = 72;

    protected static ?string $slug = 'epr';

    protected string $view = 'epr::report';

    /** @var array<string, mixed> */
    public array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('epr.manage') ?? false;
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Sales;
    }

    public static function getNavigationLabel(): string
    {
        return __('epr::admin.title');
    }

    public function getTitle(): string
    {
        return __('epr::admin.title');
    }

    public function mount(): void
    {
        $quarter = now()->subQuarterNoOverflow();
        $this->form->fill(['from' => $quarter->copy()->firstOfQuarter()->toDateString(), 'to' => $quarter->copy()->lastOfQuarter()->toDateString()]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make()->description(__('epr::admin.help'))->columns(2)->schema([
                DatePicker::make('from')->label(__('epr::admin.from'))->required()->native(false)->live(),
                DatePicker::make('to')->label(__('epr::admin.to'))->required()->native(false)->afterOrEqual('from')->live(),
            ]),
        ]);
    }

    /**
     * @return array{materials: array<string, array{products: int, shipments: int, total: int}>, orders: int}|null
     */
    public function getReportProperty(): ?array
    {
        $from = $this->data['from'] ?? null;
        $to = $this->data['to'] ?? null;

        return $from && $to ? app(Packaging::class)->report(Carbon::parse($from), Carbon::parse($to)) : null;
    }

    protected function getHeaderActions(): array
    {
        $t = fn (string $key) => __('epr::admin.'.$key);

        return [
            Action::make('shipment')->label($t('shipment'))->icon(Heroicon::OutlinedArchiveBox)->color('gray')
                ->modalDescription($t('shipment_help'))
                ->fillForm(fn () => ['packaging' => app(EprSettings::class)->shipment_packaging])
                ->schema([
                    Section::make()->columns(4)->schema(array_map(
                        fn (string $material) => TextInput::make('packaging.'.$material)->label($t('materials.'.$material))->numeric()->minValue(0)->suffix('g'),
                        Packaging::MATERIALS,
                    )),
                ])
                ->action(function (array $data) use ($t): void {
                    $settings = app(EprSettings::class);
                    $settings->shipment_packaging = array_filter(array_map(fn ($grams) => (float) $grams, (array) $data['packaging']));
                    $settings->save();
                    Notification::make()->success()->title($t('saved'))->send();
                }),
            Action::make('download')->label($t('download'))->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn () => $this->download()),
        ];
    }

    public function download(): ?StreamedResponse
    {
        $data = $this->form->getState();
        $report = app(Packaging::class)->report(Carbon::parse($data['from']), Carbon::parse($data['to']));
        $t = fn (string $key) => __('epr::admin.'.$key);

        $rows = [[$t('material'), $t('products_kg'), $t('shipments_kg'), $t('total_kg')]];

        foreach ($report['materials'] as $material => $values) {
            $rows[] = [$t('materials.'.$material), ...array_map(fn ($grams) => number_format($grams / 1000, 3, ',', ''), array_values($values))];
        }

        $csv = "\xEF\xBB\xBF".implode("\n", array_map(fn (array $row) => implode(';', $row), $rows))."\n";
        $name = 'epr-'.Carbon::parse($data['from'])->format('Ymd').'-'.Carbon::parse($data['to'])->format('Ymd').'.csv';

        return response()->streamDownload(fn () => print ($csv), $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** V menu pod položkou „OrderResource“ (zobrazí sa po jej otvorení), aby bolo hlavné menu krátke. */
    public static function getNavigationParentItem(): ?string
    {
        // Ak modul s nadradenou položkou nie je zapnutý alebo prístupný, položka ostane samostatná.
        return class_exists(OrderResource::class) && OrderResource::canAccess() ? OrderResource::getNavigationLabel() : null;
    }
}
