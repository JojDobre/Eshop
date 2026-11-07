<?php

namespace Modules\Shipping\Filament\Resources\ShippingMethods;

use App\Core\Support\Price;
use App\Filament\AdminGroup;
use App\Filament\Forms\MoneyInput;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Modules\Shipping\Filament\Resources\ShippingMethods\Pages\CreateShippingMethod;
use Modules\Shipping\Filament\Resources\ShippingMethods\Pages\EditShippingMethod;
use Modules\Shipping\Filament\Resources\ShippingMethods\Pages\ListShippingMethods;
use Modules\Shipping\Models\ShippingMethod;
use Modules\Shipping\ShippingType;
use UnitEnum;

class ShippingMethodResource extends Resource
{
    protected static ?string $model = ShippingMethod::class;

    // Slovenčina a čeština nepíšu názvy veľkými začiatočnými písmenami každého slova.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?int $navigationSort = 50;

    protected static ?string $slug = 'settings/shipping';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Settings;
    }

    public static function getNavigationLabel(): string
    {
        return __('shipping::admin.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('shipping::admin.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('shipping::admin.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('shipping.manage') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('shipping::admin.'.$key);

        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->label($t('name'))->required()->maxLength(120),
                Select::make('type')->label($t('type'))->options(ShippingType::class)->required()->live()->native(false),
                Textarea::make('description')->label($t('description'))->helperText($t('description_help'))
                    ->rows(2)->maxLength(300)->columnSpanFull(),
                Textarea::make('pickup_details')->label($t('pickup_details'))->helperText($t('pickup_details_help'))
                    ->rows(3)->maxLength(500)->columnSpanFull()
                    ->visible(fn (Get $get) => $get('type') === ShippingType::Pickup || $get('type') === ShippingType::Pickup->value),
                MoneyInput::make('price')->label($t('price'))->required()->default(0),
                MoneyInput::make('free_from')->label($t('free_from'))->helperText($t('free_from_help')),
                Repeater::make('weight_rates')->label($t('weight_rates'))->helperText($t('weight_rates_help'))
                    ->schema([
                        TextInput::make('up_to')->label($t('up_to'))->required()->rule('regex:/^\d+([.,]\d{1,3})?$/')->suffix('kg')
                            ->formatStateUsing(fn ($state) => is_numeric($state) ? rtrim(rtrim(number_format($state / 1000, 3, '.', ''), '0'), '.') : $state)
                            ->dehydrateStateUsing(fn ($state) => (int) round((float) str_replace(',', '.', (string) $state) * 1000)),
                        MoneyInput::make('price')->label($t('price'))->required(),
                    ])
                    ->columns(2)->defaultItems(0)->addActionLabel($t('add_weight_rate'))->columnSpanFull(),
                TextInput::make('position')->label($t('position'))->integer()->default(0)->minValue(0),
                Toggle::make('is_active')->label($t('is_active'))->default(true)->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('shipping::admin.'.$key);

        return $table
            ->columns([
                TextColumn::make('name')->label($t('name'))->description(fn (ShippingMethod $record) => $record->description),
                TextColumn::make('type')->label($t('type'))->badge()->color('gray'),
                TextColumn::make('price')->label($t('price'))
                    ->formatStateUsing(fn (ShippingMethod $record) => Price::format($record->price, $record->currency)),
                TextColumn::make('free_from')->label($t('free_from'))->placeholder('—')
                    ->formatStateUsing(fn (ShippingMethod $record) => $record->free_from === null ? null : Price::format($record->free_from, $record->currency)),
                ToggleColumn::make('is_active')->label($t('is_active')),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShippingMethods::route('/'),
            'create' => CreateShippingMethod::route('/create'),
            'edit' => EditShippingMethod::route('/{record}/edit'),
        ];
    }
}
