<?php

namespace Modules\Shipping\Filament\Resources\ShippingMethods;

use App\Core\Support\Currencies;
use App\Core\Support\Price;
use App\Filament\AdminGroup;
use App\Filament\Forms\MoneyInput;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\ProductType;
use Modules\Shipping\Carriers\CarrierRegistry;
use Modules\Shipping\Carriers\ShippingCarrier;
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
                TextInput::make('delivery_time')->label($t('delivery_time'))->helperText($t('delivery_time_help'))->maxLength(80),
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
                // Ceny v ďalších menách (nepovinné, inak prepočet kurzom).
                Fieldset::make(__('admin.currencies.other_prices'))
                    ->visible(fn () => app(Currencies::class)->isMulticurrency())
                    ->columnSpanFull()
                    ->schema(fn () => collect(app(Currencies::class)->enabled())->skip(1)->flatMap(fn (string $code) => [
                        MoneyInput::make("currency_prices.{$code}.price", $code)->label(__('admin.currencies.price_in', ['currency' => $code]))
                            ->helperText(__('admin.currencies.other_prices_help')),
                        MoneyInput::make("currency_prices.{$code}.free_from", $code)->label(__('admin.currencies.free_from_in', ['currency' => $code])),
                    ])->values()->all()),
                TextInput::make('position')->label($t('position'))->integer()->default(0)->minValue(0),
                Toggle::make('is_active')->label($t('is_active'))->default(true)->inline(false),
            ]),
            // Kuriér: vytváranie zásielok, štítky, sledovanie a výdajné miesta.
            Section::make($t('carriers.section'))->description($t('carriers.section_help'))->columns(2)->schema([
                Select::make('carrier')->label($t('carriers.carrier'))->placeholder($t('carriers.none'))
                    ->options(fn (Get $get) => collect(app(CarrierRegistry::class)->all())
                        ->filter(fn (ShippingCarrier $carrier) => ($type = self::type($get)) === null || $carrier->supports($type))
                        ->map(fn (ShippingCarrier $carrier) => $carrier->label())->all())
                    ->live()->native(false)
                    ->required(fn (Get $get) => self::type($get) === ShippingType::PickupPoint),
                Group::make(fn (Get $get) => app(CarrierRegistry::class)->find($get('carrier'))?->methodFields() ?? [])
                    ->statePath('carrier_options')->columns(2)->columnSpanFull(),
            ])->visible(fn (Get $get) => self::type($get) !== ShippingType::None),
            Section::make($t('restrictions.section'))->collapsed()->columns(2)->schema([
                MoneyInput::make('cost')->label($t('restrictions.cost'))->helperText($t('restrictions.cost_help')),
                Select::make('countries')->label($t('restrictions.countries'))->helperText($t('restrictions.countries_help'))
                    ->multiple()->searchable()
                    ->options(fn () => collect(config('eshop.countries'))->mapWithKeys(fn (string $code) => [$code => __('countries.'.$code)])->all())
                    ->visible(fn (Get $get) => self::type($get) === ShippingType::Delivery),
                MoneyInput::make('min_order')->label($t('restrictions.min_order'))->helperText($t('restrictions.min_order_help')),
                MoneyInput::make('max_order')->label($t('restrictions.max_order')),
                TagsInput::make('zip_prefixes')->label($t('restrictions.zip_prefixes'))->helperText($t('restrictions.zip_prefixes_help'))
                    ->visible(fn (Get $get) => self::type($get) === ShippingType::Delivery),
                Select::make('excluded_product_type_ids')->label($t('restrictions.excluded_types'))->helperText($t('restrictions.excluded_types_help'))
                    ->multiple()->options(fn () => class_exists(ProductType::class)
                        ? ProductType::query()->orderBy('position')->get()->mapWithKeys(fn ($type) => [$type->id => $type->name])->all() : []),
                Select::make('excluded_category_ids')->label($t('restrictions.excluded_categories'))->helperText($t('restrictions.excluded_categories_help'))
                    ->multiple()->searchable()->options(fn () => class_exists(Category::class)
                        ? Category::query()->with('parent.parent')->get()->mapWithKeys(fn ($category) => [$category->id => $category->pathName()])->sort()->all() : []),
            ]),
        ]);
    }

    private static function type(Get $get): ?ShippingType
    {
        $type = $get('type');

        return $type instanceof ShippingType ? $type : ShippingType::tryFrom((string) $type);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('shipping::admin.'.$key);

        return $table
            ->columns([
                TextColumn::make('name')->label($t('name'))->description(fn (ShippingMethod $record) => $record->description),
                TextColumn::make('type')->label($t('type'))->badge()->color('gray'),
                TextColumn::make('carrier')->label($t('carriers.carrier'))->placeholder('—')
                    ->formatStateUsing(fn (?string $state) => app(CarrierRegistry::class)->find($state)?->label() ?? $state),
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
