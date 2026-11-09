<?php

namespace Modules\Catalog\Filament\Resources\Categories;

use App\Filament\AdminGroup;
use App\Filament\Forms\BlockBuilder;
use App\Filament\Forms\RichText;
use App\Filament\Tables\MissingTranslationsColumn;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Catalog\CustomFields\CustomField;
use Modules\Catalog\CustomFields\CustomFieldsSection;
use Modules\Catalog\Filament\Resources\Categories\Pages\CreateCategory;
use Modules\Catalog\Filament\Resources\Categories\Pages\EditCategory;
use Modules\Catalog\Filament\Resources\Categories\Pages\ListCategories;
use Modules\Catalog\Models\Category;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    // Slovenčina a čeština nepíšu názvy veľkými začiatočnými písmenami každého slova.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Catalog;
    }

    public static function getModelLabel(): string
    {
        return __('catalog::admin.category.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('catalog::admin.category.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('catalog.manage') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('catalog::admin.category.'.$key);

        return $schema->columns(3)->components([
            Group::make()->columnSpan(2)->schema([
                Section::make()->schema([
                    TextInput::make('name')->label($t('name'))->required()->maxLength(160),
                    TextInput::make('slug')->label($t('slug'))->helperText(__('catalog::admin.slug_help'))->maxLength(180)
                        ->rule('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'),
                    RichText::make('description', 'categories')->label($t('description')),
                ]),
                CustomFieldsSection::make(CustomField::CATEGORY),
                BlockBuilder::section(__('admin.blocks.category_help')),
                Section::make(__('catalog::admin.seo'))->collapsed()->schema([
                    TextInput::make('meta_title')->label(__('catalog::admin.meta_title'))->maxLength(70),
                    Textarea::make('meta_description')->label(__('catalog::admin.meta_description'))->rows(2)->maxLength(170),
                ]),
            ]),
            Group::make()->schema([
                Section::make()->schema([
                    Toggle::make('is_visible')->label($t('is_visible'))->default(true),
                    Select::make('parent_id')
                        ->label($t('parent'))
                        ->placeholder($t('no_parent'))
                        ->options(fn (?Category $record) => Category::query()
                            ->when($record, fn ($query) => $query->whereNotIn('id', $record->descendantIdsWithSelf()))
                            ->with('parent.parent')
                            ->get()
                            ->mapWithKeys(fn (Category $category) => [$category->id => $category->pathName()])
                            ->sort())
                        ->searchable(),
                    TextInput::make('position')->label($t('position'))->numeric()->default(0)->minValue(0),
                    FileUpload::make('image')->label($t('image'))
                        ->disk('public')->directory('categories')->visibility('public')
                        ->image()->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->maxSize(4096)->imageResizeTargetWidth('1600')->imageResizeMode('contain'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('catalog::admin.category.'.$key);

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('parent.parent')->withCount('products'))
            ->columns([
                TextColumn::make('name')->label($t('name'))
                    ->formatStateUsing(fn (Category $record) => $record->pathName())
                    ->searchable(query: fn ($query, string $search) => $query->where('name', 'like', '%'.$search.'%')),
                MissingTranslationsColumn::make(),
                TextColumn::make('products_count')->label($t('products'))->badge()->color('gray'),
                IconColumn::make('is_visible')->label($t('is_visible'))->boolean(),
                TextColumn::make('position')->label($t('position'))->sortable(),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record instanceof Category ? $record->pathName() : (string) $record->getKey();
    }
}
