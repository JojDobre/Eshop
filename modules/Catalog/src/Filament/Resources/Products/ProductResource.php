<?php

namespace Modules\Catalog\Filament\Resources\Products;

use App\Core\Features\FeatureToggles;
use App\Core\Support\Currencies;
use App\Core\Support\CurrencyContext;
use App\Core\Support\Price;
use App\Filament\AdminGroup;
use App\Filament\Forms\MoneyInput;
use App\Filament\Tables\MissingTranslationsColumn;
use App\Settings\TaxSettings;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
use Modules\Catalog\CatalogFeatures;
use Modules\Catalog\CustomFields\CustomField;
use Modules\Catalog\CustomFields\CustomFieldsForm;
use Modules\Catalog\Filament\ProductRelations;
use Modules\Catalog\Filament\Resources\Manufacturers\ManufacturerResource;
use Modules\Catalog\Filament\Resources\Products\Pages\CreateProduct;
use Modules\Catalog\Filament\Resources\Products\Pages\EditProduct;
use Modules\Catalog\Filament\Resources\Products\Pages\ListProducts;
use Modules\Catalog\Filament\Resources\Products\RelationManagers\OptionsRelationManager;
use Modules\Catalog\Filament\Resources\Products\RelationManagers\StockMovementsRelationManager;
use Modules\Catalog\Filament\Resources\Products\RelationManagers\VariantsRelationManager;
use Modules\Catalog\Models\Addon;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductFile;
use Modules\Catalog\Models\ProductType;
use Modules\Catalog\Models\ProductVariant;
use Modules\Catalog\StockLots;
use Modules\Tax\Models\TaxRate;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    // Slovenčina a čeština nepíšu názvy veľkými začiatočnými písmenami každého slova.
    protected static bool $hasTitleCaseModelLabel = false;

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
                Section::make(__('catalog::admin.custom_fields.plural'))
                    // Pri zapnutých typoch produktov sa zobrazia iba polia typu (typ bez polí = všetky polia).
                    ->schema(fn () => CustomFieldsForm::components(CustomField::PRODUCT, fn (CustomField $field) => function (Get $get) use ($field): bool {
                        $type = CatalogFeatures::enabled('product_types') && $get('product_type_id') ? ProductType::query()->with('customFields')->find($get('product_type_id')) : null;

                        return $type === null || $type->customFields->isEmpty() || $type->customFields->contains('id', $field->id);
                    }))
                    ->columns(2)
                    ->visible(fn () => CustomField::query()->forEntity(CustomField::PRODUCT)->exists()),
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
                Section::make(__('catalog::admin.addon.plural'))
                    ->description(__('catalog::admin.addon.product_help'))
                    ->visible(fn () => CatalogFeatures::enabled('addons'))
                    ->collapsed()
                    ->schema([
                        Select::make('addons')->hiddenLabel()
                            ->relationship('addons', 'id')
                            ->getOptionLabelFromRecordUsing(fn (Addon $record) => $record->name.' (+'.Price::format($record->price, $record->currency).')')
                            ->multiple()->preload(),
                    ]),
                Section::make(__('catalog::admin.digital.title'))
                    ->description(__('catalog::admin.digital.help'))
                    ->visible(fn () => CatalogFeatures::enabled('digital'))
                    ->collapsed(fn (?Product $record) => ! $record?->is_digital)
                    ->schema([
                        Toggle::make('is_digital')->label(__('catalog::admin.digital.is_digital'))->helperText(__('catalog::admin.digital.is_digital_help'))->live(),
                        Repeater::make('files')->hiddenLabel()
                            ->relationship('files')
                            ->visible(fn (Get $get) => (bool) $get('is_digital'))
                            ->orderColumn('position')
                            ->columns(2)
                            ->addActionLabel(__('catalog::admin.digital.add_file'))
                            ->schema([
                                FileUpload::make('path')->label(__('catalog::admin.digital.file'))
                                    // Súkromný disk: súbor sa dá stiahnuť iba cez odkaz z objednávky.
                                    ->disk(ProductFile::DISK)->directory(ProductFile::DIRECTORY)->visibility('private')
                                    ->storeFileNamesIn('name')->maxSize(512000)->required()->downloadable(),
                                TextInput::make('name')->label(__('catalog::admin.digital.name'))->helperText(__('catalog::admin.digital.name_help'))->maxLength(255),
                            ]),
                    ]),
                Section::make(__('catalog::admin.bundle.title'))
                    ->description(__('catalog::admin.bundle.help'))
                    ->visible(fn () => CatalogFeatures::enabled('bundles'))
                    ->collapsed(fn (?Product $record) => ! $record?->is_bundle)
                    ->schema([
                        Toggle::make('is_bundle')->label(__('catalog::admin.bundle.is_bundle'))->live(),
                        Repeater::make('bundleItems')->hiddenLabel()
                            ->relationship('bundleItems')
                            ->visible(fn (Get $get) => (bool) $get('is_bundle'))
                            ->orderColumn('position')
                            ->columns(3)
                            ->addActionLabel(__('catalog::admin.bundle.add_item'))
                            ->schema([
                                Select::make('product_id')->label(__('catalog::admin.bundle.product'))
                                    // Balíček nemôže obsahovať seba ani iný balíček.
                                    ->options(fn ($livewire) => Product::query()
                                        ->where('is_bundle', false)
                                        ->when(($livewire->record ?? null) instanceof Product, fn ($query) => $query->whereKeyNot($livewire->record->id))
                                        ->orderBy('id')->get()->mapWithKeys(fn (Product $product) => [$product->id => $product->name])->all())
                                    ->searchable()->required()->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('product_variant_id', null)),
                                Select::make('product_variant_id')->label(__('catalog::admin.bundle.variant'))
                                    ->options(fn (Get $get) => ProductVariant::query()
                                        ->where('product_id', $get('product_id'))->get()
                                        ->mapWithKeys(fn ($variant) => [$variant->id => $variant->name()])->all())
                                    ->visible(fn (Get $get) => filled($get('product_id')) && ProductVariant::query()->where('product_id', $get('product_id'))->exists())
                                    ->required(fn (Get $get) => filled($get('product_id')) && ProductVariant::query()->where('product_id', $get('product_id'))->exists()),
                                TextInput::make('quantity')->label(__('catalog::admin.bundle.quantity'))->numeric()->integer()->minValue(1)->default(1)->required(),
                            ]),
                    ]),
                Section::make($t('section_safety'))->description($t('section_safety_help'))->collapsed()->schema([
                    Select::make('manufacturer_id')->label($t('manufacturer'))
                        ->relationship('manufacturer', 'name')
                        ->searchable()->preload()
                        ->createOptionForm(ManufacturerResource::formFields()),
                    Textarea::make('safety_info')->label($t('safety_info'))->helperText($t('safety_info_help'))->rows(3)->maxLength(5000),
                ]),
                Section::make(__('catalog::admin.seo'))->collapsed()->schema([
                    TextInput::make('meta_title')->label(__('catalog::admin.meta_title'))->maxLength(70),
                    Textarea::make('meta_description')->label(__('catalog::admin.meta_description'))->rows(2)->maxLength(170),
                ]),
            ]),
            Group::make()->schema([
                Section::make()->schema([
                    Toggle::make('is_visible')->label($t('is_visible'))->helperText($t('is_visible_help'))->default(true),
                    Select::make('product_type_id')->label(__('catalog::admin.product_type.label'))
                        ->options(fn () => ProductType::query()->ordered()->get()->mapWithKeys(fn (ProductType $type) => [$type->id => $type->name])->all())
                        ->placeholder(__('catalog::admin.product_type.none'))
                        ->live()->native(false)
                        ->visible(fn () => CatalogFeatures::enabled('product_types')),
                    TextInput::make('unit_amount')->label(__('catalog::admin.product_type.unit_amount'))
                        ->helperText(__('catalog::admin.product_type.unit_amount_help'))
                        ->numeric()->minValue(0.0001)
                        ->suffix(fn (Get $get) => ($type = $get('product_type_id') ? ProductType::find($get('product_type_id')) : null) ? __('catalog::admin.product_type.units.'.$type->unit) : null)
                        ->visible(fn (Get $get) => CatalogFeatures::enabled('product_types') && $get('product_type_id') && ProductType::find($get('product_type_id'))?->hasUnitPrice()),
                    Select::make('categories')->label($t('categories'))
                        ->relationship('categories', 'id')
                        ->getOptionLabelFromRecordUsing(fn (Category $record) => $record->pathName())
                        ->multiple()->preload()->searchable(),
                ]),
                Section::make($t('section_price'))->schema([
                    TextInput::make('price')->label($t('price'))
                        ->helperText(fn () => app(TaxSettings::class)->vat_payer && ! app(TaxSettings::class)->prices_include_vat ? $t('price_help_net') : $t('price_help'))
                        ->required()->rule(MoneyInput::RULE)->inputMode('decimal')->suffix($currency),
                    TextInput::make('compare_at_price')->label($t('compare_at_price'))->helperText($t('compare_at_price_help'))
                        ->rule(MoneyInput::RULE)->inputMode('decimal')->suffix($currency),
                    Select::make('tax_rate_id')->label($t('tax_rate'))
                        ->relationship('taxRate', 'id')
                        ->getOptionLabelFromRecordUsing(fn (TaxRate $record) => $record->label())
                        ->placeholder(fn () => ($default = TaxRate::default()) ? $t('tax_rate_default').': '.$default->label() : null)
                        ->visible(fn () => app(TaxSettings::class)->vat_payer),
                    TextInput::make('sku')->label($t('sku'))->maxLength(64)->unique(ignoreRecord: true),
                    TextInput::make('ean')->label($t('ean'))->helperText($t('ean_help'))->rule('regex:/^\d{8,14}$/')->maxLength(14),
                    // Predajom vznikne darčeková poukážka (funkcia modulu objednávok).
                    Toggle::make('is_gift_voucher')->label($t('is_gift_voucher'))->helperText($t('is_gift_voucher_help'))
                        ->visible(fn () => (bool) (($toggle = app(FeatureToggles::class)->find('orders.gift_vouchers')) && ($toggle->isEnabled)())),
                ]),
                // Ceny v ďalších menách: nepovinné, inak sa prepočítajú kurzom.
                Section::make(__('admin.currencies.other_prices'))
                    ->visible(fn () => app(Currencies::class)->isMulticurrency())
                    ->collapsible()
                    ->schema(fn () => collect(app(Currencies::class)->enabled())->skip(1)->map(fn (string $code) => Grid::make(2)->schema([
                        TextInput::make("other_prices.{$code}.price")->label(__('admin.currencies.price_in', ['currency' => $code]))
                            ->rule(MoneyInput::RULE)->inputMode('decimal')->suffix($code)
                            ->placeholder(fn (?Product $record) => ($converted = $record?->priceIn($code)) !== null ? Price::toDecimal($converted, $code) : null)
                            ->helperText(__('admin.currencies.other_prices_help')),
                        TextInput::make("other_prices.{$code}.compare_at")->label(__('admin.currencies.compare_at_in', ['currency' => $code]))
                            ->rule(MoneyInput::RULE)->inputMode('decimal')->suffix($code),
                    ]))->values()->all()),
                Section::make($t('section_stock'))->schema([
                    Toggle::make('track_stock')->label($t('track_stock'))->helperText($t('track_stock_help'))->default(true)->live(),
                    TextInput::make('stock_quantity')->label($t('stock_quantity'))->integer()->default(0)
                        // Pri skladoch a šaržiach sa sklad mení príjmom a inventúrou v časti „Zásoby“.
                        ->disabled(fn (?Product $record) => $record !== null && app(StockLots::class)->enabled())
                        ->helperText(fn (?Product $record) => $record !== null && app(StockLots::class)->enabled() ? __('catalog::admin.lots.managed_in_lots') : null)
                        ->visible(fn (Get $get, ?Product $record) => (bool) $get('track_stock') && ! $record?->hasVariants()),
                    Text::make(__('catalog::admin.variants.stock_on_variants'))
                        ->visible(fn (Get $get, ?Product $record) => (bool) $get('track_stock') && (bool) $record?->hasVariants()),
                    TextInput::make('low_stock_threshold')->label($t('low_stock_threshold'))->helperText($t('low_stock_threshold_help'))
                        ->integer()->minValue(0)->visible(fn (Get $get) => (bool) $get('track_stock')),
                    Toggle::make('preorder_enabled')->label(__('catalog::admin.preorder.enabled'))->helperText(__('catalog::admin.preorder.enabled_help'))->live()
                        ->visible(fn (Get $get) => (bool) $get('track_stock') && CatalogFeatures::enabled('preorders')),
                    DatePicker::make('preorder_available_at')->label(__('catalog::admin.preorder.available_at'))->helperText(__('catalog::admin.preorder.available_at_help'))
                        ->visible(fn (Get $get) => (bool) $get('track_stock') && (bool) $get('preorder_enabled') && CatalogFeatures::enabled('preorders')),
                    TextInput::make('preorder_limit')->label(__('catalog::admin.preorder.limit'))->helperText(__('catalog::admin.preorder.limit_help'))
                        ->integer()->minValue(1)->suffix(__('catalog::admin.pieces'))
                        ->visible(fn (Get $get) => (bool) $get('track_stock') && (bool) $get('preorder_enabled') && CatalogFeatures::enabled('preorders')),
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
                MissingTranslationsColumn::make(),
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

    public static function getRelations(): array
    {
        return [
            OptionsRelationManager::class,
            VariantsRelationManager::class,
            RelationManagers\GroupPricesRelationManager::class,
            RelationManagers\QuantityDiscountsRelationManager::class,
            RelationManagers\StockLotsRelationManager::class,
            StockMovementsRelationManager::class,
            ...ProductRelations::all(),
        ];
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
