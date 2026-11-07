<?php

namespace Modules\Catalog\Filament\Resources\Addons;

use App\Core\Support\Price;
use App\Filament\AdminGroup;
use App\Filament\Concerns\FillsContentTranslations;
use App\Filament\Forms\MoneyInput;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Modules\Catalog\CatalogFeatures;
use Modules\Catalog\Filament\Resources\Addons\Pages\ManageAddons;
use Modules\Catalog\Models\Addon;
use Modules\Catalog\Models\Product;
use UnitEnum;

class AddonResource extends Resource
{
    protected static ?string $model = Addon::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static ?int $navigationSort = 26;

    protected static ?string $slug = 'addons';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Catalog;
    }

    public static function getModelLabel(): string
    {
        return __('catalog::admin.addon.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('catalog::admin.addon.plural');
    }

    public static function canAccess(): bool
    {
        return CatalogFeatures::enabled('addons') && (auth()->user()?->can('catalog.manage') ?? false);
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('catalog::admin.addon.'.$key);

        return $schema->columns(2)->components([
            TextInput::make('name')->label($t('name'))->required()->maxLength(120),
            MoneyInput::make('price')->label($t('price'))->helperText($t('price_help'))->required()->default(0),
            TextInput::make('description')->label($t('description'))->maxLength(255)->columnSpanFull(),
            Toggle::make('needs_text')->label($t('needs_text'))->helperText($t('needs_text_help'))->live(),
            TextInput::make('text_label')->label($t('text_label'))->placeholder($t('text_label_placeholder'))->maxLength(80)
                ->visible(fn (Get $get) => (bool) $get('needs_text')),
            TextInput::make('text_max_length')->label($t('text_max_length'))->integer()->minValue(1)->maxValue(500)->default(50)
                ->visible(fn (Get $get) => (bool) $get('needs_text')),
            Select::make('products')->label($t('products'))->helperText($t('products_help'))
                ->relationship('products', 'id')
                ->getOptionLabelFromRecordUsing(fn (Product $record) => $record->name)
                ->multiple()->searchable()->preload()->columnSpanFull(),
            TextInput::make('position')->label($t('position'))->integer()->default(0),
            Toggle::make('is_active')->label($t('is_active'))->default(true)->inline(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('catalog::admin.addon.'.$key);

        return $table
            ->columns([
                TextColumn::make('name')->label($t('name'))->weight('medium')->description(fn (Addon $record) => $record->description),
                TextColumn::make('price')->label($t('price'))->formatStateUsing(fn (Addon $record) => Price::format($record->price, $record->currency)),
                IconColumn::make('needs_text')->label($t('needs_text'))->boolean(),
                TextColumn::make('products_count')->label($t('products'))->counts('products'),
                ToggleColumn::make('is_active')->label($t('is_active')),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->recordActions([
                EditAction::make()->mutateRecordDataUsing(fn (array $data, Addon $record) => FillsContentTranslations::contentTranslationsForForm($record, $data)),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAddons::route('/')];
    }
}
