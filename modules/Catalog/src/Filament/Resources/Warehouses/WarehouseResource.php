<?php

namespace Modules\Catalog\Filament\Resources\Warehouses;

use App\Filament\AdminGroup;
use App\Filament\Concerns\FillsContentTranslations;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Modules\Catalog\CatalogFeatures;
use Modules\Catalog\Filament\Resources\Warehouses\Pages\ManageWarehouses;
use Modules\Catalog\Models\Warehouse;
use UnitEnum;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?int $navigationSort = 40;

    protected static ?string $slug = 'warehouses';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Catalog;
    }

    public static function getModelLabel(): string
    {
        return __('catalog::admin.warehouse.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('catalog::admin.warehouse.plural');
    }

    public static function canAccess(): bool
    {
        return CatalogFeatures::enabled('warehouses') && (auth()->user()?->can('catalog.manage') ?? false);
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('catalog::admin.warehouse.'.$key);

        return $schema->columns(2)->components([
            TextInput::make('name')->label($t('name'))->required()->maxLength(120),
            TextInput::make('code')->label($t('code'))->maxLength(32),
            Textarea::make('address')->label($t('address'))->rows(2)->maxLength(500)->columnSpanFull(),
            Toggle::make('is_active')->label($t('is_active'))->helperText($t('is_active_help'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('catalog::admin.warehouse.'.$key);

        return $table
            ->modifyQueryUsing(fn ($query) => $query->withSum('lots', 'quantity'))
            ->columns([
                TextColumn::make('name')->label($t('name'))->description(fn (Warehouse $record) => $record->code),
                TextColumn::make('lots_sum_quantity')->label($t('stock'))->badge()->color('gray')->default(0),
                ToggleColumn::make('is_active')->label($t('is_active')),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->description($t('order_help'))
            ->recordActions([
                EditAction::make()->mutateRecordDataUsing(fn (array $data, Warehouse $record) => FillsContentTranslations::contentTranslationsForForm($record, $data)),
                // Sklad so zásobami sa nedá zmazať (najprv presun alebo odpis).
                DeleteAction::make()->hidden(fn (Warehouse $record) => $record->lots()->where('quantity', '!=', 0)->exists() || Warehouse::query()->count() === 1),
            ])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return ['index' => ManageWarehouses::route('/')];
    }
}
