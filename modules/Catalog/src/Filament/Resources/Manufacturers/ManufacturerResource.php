<?php

namespace Modules\Catalog\Filament\Resources\Manufacturers;

use App\Filament\AdminGroup;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Catalog\Filament\Resources\Manufacturers\Pages\ManageManufacturers;
use Modules\Catalog\Models\Manufacturer;
use UnitEnum;

class ManufacturerResource extends Resource
{
    protected static ?string $model = Manufacturer::class;

    // Slovenčina a čeština nepíšu názvy veľkými začiatočnými písmenami každého slova.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Catalog;
    }

    public static function getModelLabel(): string
    {
        return __('catalog::admin.manufacturer.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('catalog::admin.manufacturer.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('catalog.manage') ?? false;
    }

    /**
     * Polia formulára (použité aj pri rýchlom vytvorení výrobcu priamo vo formulári produktu).
     *
     * @return array<int, mixed>
     */
    public static function formFields(): array
    {
        $t = fn (string $key) => __('catalog::admin.manufacturer.'.$key);

        return [
            TextInput::make('name')->label($t('name'))->required()->maxLength(200),
            Textarea::make('address')->label($t('address'))->helperText($t('address_help'))->rows(2)->maxLength(500),
            TextInput::make('email')->label($t('email'))->email()->maxLength(255),
            TextInput::make('website')->label($t('website'))->url()->maxLength(255),
            Textarea::make('eu_responsible_person')->label($t('eu_responsible_person'))->helperText($t('eu_responsible_person_help'))->rows(3)->maxLength(1000),
            Textarea::make('safety_info')->label($t('safety_info'))->helperText($t('safety_info_help'))->rows(3)->maxLength(5000),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components(self::formFields());
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('catalog::admin.manufacturer.'.$key);

        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('products'))
            ->columns([
                TextColumn::make('name')->label($t('name'))->searchable()->sortable(),
                TextColumn::make('email')->label($t('email'))->placeholder('—'),
                TextColumn::make('products_count')->label($t('products'))->badge()->color('gray'),
            ])
            ->defaultSort('name')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageManufacturers::route('/')];
    }
}
