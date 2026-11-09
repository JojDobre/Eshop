<?php

namespace Modules\Tax\Filament\Resources\TaxRates;

use App\Filament\AdminGroup;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Tax\EuVat;
use Modules\Tax\Filament\Pages\ManageTaxSettings;
use Modules\Tax\Filament\Resources\TaxRates\Pages\ManageTaxRates;
use Modules\Tax\Models\TaxRate;
use UnitEnum;

class TaxRateResource extends Resource
{
    protected static ?string $model = TaxRate::class;

    // Slovenčina a čeština nepíšu názvy veľkými začiatočnými písmenami každého slova.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?int $navigationSort = 36;

    protected static ?string $slug = 'settings/tax-rates';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Settings;
    }

    public static function getNavigationLabel(): string
    {
        return __('tax::admin.rates.plural');
    }

    public static function getModelLabel(): string
    {
        return __('tax::admin.rates.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('tax::admin.rates.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('tax.manage') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('tax::admin.rates.'.$key);

        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->label($t('name'))->required()->maxLength(80),
                TextInput::make('rate')->label($t('rate'))->required()->numeric()->minValue(0)->maxValue(100)->step('0.01')->suffix('%'),
                TextInput::make('position')->label($t('position'))->integer()->default(0),
                Toggle::make('is_default')->label($t('is_default'))->helperText($t('is_default_help'))->inline(false),
            ]),
            // Sadzby v iných krajinách EÚ (režim OSS).
            Section::make($t('country_rates'))->description($t('country_rates_help'))->collapsed()->schema([
                KeyValue::make('country_rates')->hiddenLabel()
                    ->keyLabel($t('country'))->valueLabel($t('rate'))
                    ->addActionLabel($t('add_country_rate'))
                    ->rules(['array'])
                    ->dehydrateStateUsing(fn (?array $state) => collect($state ?? [])
                        ->mapWithKeys(fn ($rate, $country) => [strtoupper(trim((string) $country)) => (float) str_replace(',', '.', (string) $rate)])
                        ->filter(fn ($rate, $country) => EuVat::isMember($country))
                        ->all()),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('tax::admin.rates.'.$key);

        return $table
            ->columns([
                TextColumn::make('name')->label($t('name')),
                TextColumn::make('rate')->label($t('rate'))->suffix(' %'),
                IconColumn::make('is_default')->label($t('is_default'))->boolean(),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return ['index' => ManageTaxRates::route('/')];
    }

    /** V menu pod položkou „ManageTaxSettings“ (zobrazí sa po jej otvorení). */
    public static function getNavigationParentItem(): ?string
    {
        return ManageTaxSettings::getNavigationLabel();
    }
}
