<?php

namespace Modules\Catalog\Filament\Resources\ProductLabels;

use App\Filament\AdminGroup;
use App\Filament\Concerns\FillsContentTranslations;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Catalog\Filament\Resources\ProductLabels\Pages\ManageProductLabels;
use Modules\Catalog\Filament\Resources\Products\ProductResource;
use Modules\Catalog\Models\ProductLabel;
use UnitEnum;

/**
 * Vlastné štítky produktov (Vlajková loď, Bio, Domáce…) s farbou pozadia a textu.
 */
class ProductLabelResource extends Resource
{
    protected static ?string $model = ProductLabel::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'product-labels';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Catalog;
    }

    /** V menu pod položkou „Produkty“. */
    public static function getNavigationParentItem(): ?string
    {
        return ProductResource::getNavigationLabel();
    }

    public static function getModelLabel(): string
    {
        return __('catalog::admin.product_labels.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('catalog::admin.product_labels.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('catalog.manage') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('catalog::admin.product_labels.'.$key);

        return $schema->columns(2)->components([
            TextInput::make('name')->label($t('name'))->required()->maxLength(40),
            ColorPicker::make('color')->label($t('color'))->required()->rule('regex:/^#[0-9a-fA-F]{6}$/')->default('#e8772a'),
            ColorPicker::make('text_color')->label($t('text_color'))->required()->rule('regex:/^#[0-9a-fA-F]{6}$/')->default('#ffffff'),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('catalog::admin.product_labels.'.$key);

        return $table
            ->columns([
                TextColumn::make('name')->label($t('name'))->badge()->color('gray'),
                ColorColumn::make('color')->label($t('color')),
                TextColumn::make('products_count')->label($t('products'))->counts('products'),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->recordActions([
                EditAction::make()->mutateRecordDataUsing(fn (array $data, ProductLabel $record) => FillsContentTranslations::contentTranslationsForForm($record, $data)),
                DeleteAction::make(),
            ])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return ['index' => ManageProductLabels::route('/')];
    }
}
