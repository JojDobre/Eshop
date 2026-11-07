<?php

namespace Modules\Catalog\Filament\Resources\Products;

use App\Core\Support\CurrencyContext;
use App\Core\Support\Price;
use App\Filament\AdminGroup;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\Filament\Resources\Products\Pages\CreateProduct;
use Modules\Catalog\Filament\Resources\Products\Pages\EditProduct;
use Modules\Catalog\Filament\Resources\Products\Pages\ListProducts;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Catalog;
    }

    public static function getModelLabel(): string
    {
        return __('catalog::admin.product.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('catalog::admin.product.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('catalog.manage') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('catalog::admin.product.'.$key);
        $currency = app(CurrencyContext::class)->current();

        return $schema->columns(3)->components([
            Group::make()->columnSpan(2)->schema([
                Section::make()->schema([
                    TextInput::make('name')->label($t('name'))->required()->maxLength(200),
                    TextInput::make('slug')->label($t('slug'))->helperText(__('catalog::admin.slug_help'))->maxLength(220)
                        ->rule('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'),
                    Textarea::make('short_description')->label($t('short_description'))->helperText($t('short_description_help'))
                        ->rows(2)->maxLength(500),
                    RichEditor::make('description')->label($t('description'))
                        ->toolbarButtons([['bold', 'italic', 'underline', 'link'], ['h2', 'h3'], ['bulletList', 'orderedList', 'table'], ['undo', 'redo']]),
                ]),
                Section::make($t('images'))->schema([
                    FileUpload::make('images')->hiddenLabel()
                        ->helperText($t('images_help'))
                        ->disk('public')->directory('products')->visibility('public')
                        ->image()->multiple()->reorderable()->appendFiles()->panelLayout('grid')
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->maxFiles(20)->maxSize(8192)
                        // Zmenšenie už v prehliadači: rýchlejšie nahrávanie a menej miesta na hostingu.
                        ->imageResizeTargetWidth('2000')->imageResizeTargetHeight('2000')->imageResizeMode('contain')
                        ->imageResizeUpscale(false),
                ]),
                Section::make(__('catalog::admin.seo'))->collapsed()->schema([
                    TextInput::make('meta_title')->label(__('catalog::admin.meta_title'))->maxLength(70),
                    Textarea::make('meta_description')->label(__('catalog::admin.meta_description'))->rows(2)->maxLength(170),
                ]),
            ]),
            Group::make()->schema([
                Section::make()->schema([
                    Toggle::make('is_visible')->label($t('is_visible'))->helperText($t('is_visible_help'))->default(true),
                    Select::make('categories')->label($t('categories'))
                        ->relationship('categories', 'id')
                        ->getOptionLabelFromRecordUsing(fn (Category $record) => $record->pathName())
                        ->multiple()->preload()->searchable(),
                ]),
                Section::make($t('section_price'))->schema([
                    TextInput::make('price')->label($t('price'))->helperText($t('price_help'))
                        ->required()->numeric()->minValue(0)->step('0.01')->suffix($currency),
                    TextInput::make('sku')->label($t('sku'))->maxLength(64)->unique(ignoreRecord: true),
                ]),
                Section::make($t('section_stock'))->schema([
                    Toggle::make('track_stock')->label($t('track_stock'))->helperText($t('track_stock_help'))->default(true)->live(),
                    TextInput::make('stock_quantity')->label($t('stock_quantity'))->integer()->default(0)
                        ->visible(fn (Get $get) => (bool) $get('track_stock')),
                    TextInput::make('weight')->label($t('weight'))->integer()->minValue(0)->suffix('g'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('catalog::admin.product.'.$key);

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['prices', 'categories']))
            ->columns([
                ImageColumn::make('images')->label('')->disk('public')->limit(1)->square()->imageSize(40),
                TextColumn::make('name')->label($t('name'))
                    ->description(fn (Product $record) => $record->sku)
                    ->searchable(query: fn (Builder $query, string $search) => $query->search($search))
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('name->'.app(Product::class)->getLocale(), $direction))
                    ->wrap(),
                TextColumn::make('price')->label($t('price'))
                    ->state(fn (Product $record) => $record->priceIn())
                    ->formatStateUsing(fn (?int $state) => $state === null ? '—' : Price::format($state, app(CurrencyContext::class)->current())),
                TextColumn::make('stock_quantity')->label($t('stock_quantity'))
                    ->badge()
                    ->state(fn (Product $record) => $record->track_stock ? $record->stock_quantity : '∞')
                    ->color(fn (Product $record) => ! $record->track_stock ? 'gray' : ($record->stock_quantity > 0 ? 'success' : 'danger'))
                    ->sortable(),
                TextColumn::make('categories.id')->label($t('categories'))
                    ->formatStateUsing(fn ($state, Product $record) => $record->categories->map->name->implode(', '))
                    ->limit(40)->toggleable(),
                ToggleColumn::make('is_visible')->label($t('is_visible')),
                TextColumn::make('updated_at')->label($t('updated_at'))->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('categories')->label($t('categories'))
                    ->relationship('categories', 'id')
                    ->getOptionLabelFromRecordUsing(fn (Category $record) => $record->pathName())
                    ->multiple()->preload(),
                TernaryFilter::make('is_visible')->label($t('is_visible')),
                Filter::make('sold_out')->label($t('sold_out'))
                    ->query(fn (Builder $query) => $query->where('track_stock', true)->where('stock_quantity', '<=', 0)),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('show')->label($t('bulk_show'))->icon(Heroicon::OutlinedEye)
                        ->action(fn (Collection $records) => $records->each->update(['is_visible' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('hide')->label($t('bulk_hide'))->icon(Heroicon::OutlinedEyeSlash)
                        ->action(fn (Collection $records) => $records->each->update(['is_visible' => false]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['search_text'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
