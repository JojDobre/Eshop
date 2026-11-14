<?php

namespace Modules\Catalog\Filament\Resources\ProductTypes;

use App\Filament\AdminGroup;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Catalog\CatalogFeatures;
use Modules\Catalog\CustomFields\CustomField;
use Modules\Catalog\Filament\Resources\Products\ProductResource;
use Modules\Catalog\Filament\Resources\ProductTypes\Pages\ManageProductTypes;
use Modules\Catalog\Models\ProductType;
use UnitEnum;

class ProductTypeResource extends Resource
{
    protected static ?string $model = ProductType::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = 25;

    protected static ?string $slug = 'product-types';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Catalog;
    }

    public static function getModelLabel(): string
    {
        return __('catalog::admin.product_type.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('catalog::admin.product_type.plural');
    }

    public static function canAccess(): bool
    {
        return CatalogFeatures::enabled('product_types') && (auth()->user()?->can('catalog.manage') ?? false);
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('catalog::admin.product_type.'.$key);

        return $schema->columns(2)->components([
            TextInput::make('name')->label($t('name'))->required()->maxLength(120),
            Select::make('unit')->label($t('unit'))->helperText($t('unit_help'))
                ->options(collect(ProductType::UNITS)->mapWithKeys(fn (string $unit) => [$unit => $t('units_long.'.$unit)])->all())
                ->default('pcs')->required()->native(false),
            Toggle::make('requires_shipping')->label($t('requires_shipping'))->helperText($t('requires_shipping_help'))->default(true),
            Toggle::make('is_unique')->label($t('is_unique'))->helperText($t('is_unique_help')),
            Select::make('customFields')->label($t('custom_fields'))->helperText($t('custom_fields_help'))
                ->relationship('customFields', 'key', fn ($query) => $query->where('entity', CustomField::PRODUCT))
                ->getOptionLabelFromRecordUsing(fn (CustomField $record) => $record->name)
                ->multiple()->preload()->columnSpanFull(),
            TextInput::make('position')->label($t('position'))->integer()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('catalog::admin.product_type.'.$key);

        return $table
            ->columns([
                TextColumn::make('name')->label($t('name'))->weight('medium'),
                TextColumn::make('unit')->label($t('unit'))->formatStateUsing(fn (string $state) => $t('units_long.'.$state)),
                IconColumn::make('requires_shipping')->label($t('requires_shipping'))->boolean(),
                IconColumn::make('is_unique')->label($t('is_unique'))->boolean(),
                TextColumn::make('products_count')->label($t('products'))->counts('products'),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageProductTypes::route('/')];
    }

    /** V menu pod položkou „ProductResource“ (zobrazí sa po jej otvorení), aby bolo hlavné menu krátke. */
    public static function getNavigationParentItem(): ?string
    {
        return ProductResource::getNavigationLabel();
    }
}
