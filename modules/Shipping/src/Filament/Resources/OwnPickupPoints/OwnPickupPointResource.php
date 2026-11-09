<?php

namespace Modules\Shipping\Filament\Resources\OwnPickupPoints;

use App\Filament\AdminGroup;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Shipping\Carriers\OwnPointsCarrier;
use Modules\Shipping\Filament\Resources\OwnPickupPoints\Pages\ManageOwnPickupPoints;
use Modules\Shipping\Filament\Resources\ShippingMethods\ShippingMethodResource;
use Modules\Shipping\Models\PickupPoint;
use UnitEnum;

/**
 * Vlastné výdajné miesta obchodu: predajne, sklady, partnerské výdajné miesta. Ponúkajú sa v pokladni
 * pri spôsobe dopravy „Výdajné miesto“ s kuriérom „Vlastné výdajné miesta“.
 */
class OwnPickupPointResource extends Resource
{
    protected static ?string $model = PickupPoint::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?int $navigationSort = 51;

    protected static ?string $slug = 'settings/own-pickup-points';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Settings;
    }

    public static function getModelLabel(): string
    {
        return __('shipping::admin.own_points.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('shipping::admin.own_points.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('shipping.manage') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('carrier', OwnPointsCarrier::KEY);
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('shipping::admin.own_points.'.$key);

        return $schema->columns(2)->components([
            TextInput::make('name')->label($t('name'))->required()->maxLength(255)->columnSpanFull(),
            TextInput::make('street')->label($t('street'))->required()->maxLength(255),
            TextInput::make('city')->label($t('city'))->required()->maxLength(120),
            TextInput::make('zip')->label($t('zip'))->required()->maxLength(16),
            Select::make('country')->label($t('country'))->required()->searchable()->default(fn () => collect(config('eshop.countries'))->first())
                ->options(fn () => collect(config('eshop.countries'))->mapWithKeys(fn (string $code) => [$code => __('countries.'.$code)])->all()),
            Textarea::make('opening_hours')->label($t('opening_hours'))->helperText($t('opening_hours_help'))->rows(2)->maxLength(500)->columnSpanFull(),
            TextInput::make('latitude')->label($t('latitude'))->numeric()->minValue(-90)->maxValue(90),
            TextInput::make('longitude')->label($t('longitude'))->numeric()->minValue(-180)->maxValue(180),
            Toggle::make('is_active')->label($t('is_active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('shipping::admin.own_points.'.$key);

        return $table
            ->columns([
                TextColumn::make('name')->label($t('name'))->searchable()->weight('medium')->description(fn (PickupPoint $record) => $record->address()),
                TextColumn::make('opening_hours')->label($t('opening_hours'))->placeholder('—')->wrap()->toggleable(),
                ToggleColumn::make('is_active')->label($t('is_active')),
            ])
            ->defaultSort('name')
            ->description($t('help'))
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageOwnPickupPoints::route('/')];
    }

    /** V menu pod položkou „ShippingMethodResource“ (zobrazí sa po jej otvorení). */
    public static function getNavigationParentItem(): ?string
    {
        return ShippingMethodResource::getNavigationLabel();
    }
}
