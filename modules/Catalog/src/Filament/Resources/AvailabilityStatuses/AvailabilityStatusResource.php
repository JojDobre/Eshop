<?php

namespace Modules\Catalog\Filament\Resources\AvailabilityStatuses;

use App\Filament\AdminGroup;
use App\Filament\Concerns\FillsContentTranslations;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Catalog\Filament\Resources\AvailabilityStatuses\Pages\ManageAvailabilityStatuses;
use Modules\Catalog\Filament\Resources\Products\ProductResource;
use Modules\Catalog\Models\AvailabilityStatus;
use UnitEnum;

/**
 * Vlastné stavy dostupnosti (Skladom, Na objednávku, Nedostupné…), ktoré sa pri produkte zobrazia namiesto stavu skladu.
 */
class AvailabilityStatusResource extends Resource
{
    protected static ?string $model = AvailabilityStatus::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static ?int $navigationSort = 12;

    protected static ?string $slug = 'availability-statuses';

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
        return __('catalog::admin.availability.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('catalog::admin.availability.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('catalog.manage') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('catalog::admin.availability.'.$key);

        return $schema->columns(2)->components([
            TextInput::make('name')->label($t('name'))->required()->maxLength(60),
            ColorPicker::make('color')->label($t('color'))->required()->rule('regex:/^#[0-9a-fA-F]{6}$/')->default('#43a047'),
            Toggle::make('is_purchasable')->label($t('is_purchasable'))->helperText($t('is_purchasable_help'))->default(true)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('catalog::admin.availability.'.$key);

        return $table
            ->columns([
                TextColumn::make('name')->label($t('name')),
                ColorColumn::make('color')->label($t('color')),
                IconColumn::make('is_purchasable')->label($t('is_purchasable'))->boolean(),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->recordActions([
                EditAction::make()->mutateRecordDataUsing(fn (array $data, AvailabilityStatus $record) => FillsContentTranslations::contentTranslationsForForm($record, $data)),
                DeleteAction::make(),
            ])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAvailabilityStatuses::route('/')];
    }
}
