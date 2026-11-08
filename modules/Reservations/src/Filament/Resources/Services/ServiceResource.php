<?php

namespace Modules\Reservations\Filament\Resources\Services;

use App\Filament\Concerns\FillsContentTranslations;
use App\Filament\Forms\MoneyInput;
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
use Modules\Reservations\Filament\ReservationsNav;
use Modules\Reservations\Filament\Resources\Services\Pages\ManageServices;
use Modules\Reservations\Models\BookingService;

class ServiceResource extends Resource
{
    use ReservationsNav;

    protected static ?string $model = BookingService::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?int $navigationSort = 51;

    protected static ?string $slug = 'reservation-services';

    public static function getModelLabel(): string
    {
        return __('reservations::admin.services.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('reservations::admin.services.plural');
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('reservations::admin.services.'.$key);

        return $schema->columns(2)->components([
            TextInput::make('name')->label($t('name'))->required()->maxLength(120),
            Select::make('resources')->label($t('resources'))->helperText($t('resources_help'))->relationship('resources', 'name')->multiple()->preload(),
            Textarea::make('description')->label($t('description'))->rows(2)->maxLength(1000)->columnSpanFull(),
            TextInput::make('duration_minutes')->label($t('duration'))->required()->integer()->minValue(5)->maxValue(1440)->suffix('min')->default(60),
            TextInput::make('buffer_minutes')->label($t('buffer'))->helperText($t('buffer_help'))->integer()->minValue(0)->maxValue(240)->suffix('min')->default(0),
            TextInput::make('capacity')->label($t('capacity'))->helperText($t('capacity_help'))->required()->integer()->minValue(1)->maxValue(500)->default(1),
            MoneyInput::make('price')->label($t('price'))->default(0),
            Select::make('payment_mode')->label($t('payment_mode'))->selectablePlaceholder(false)->default('on_site')->live()
                ->options(collect(['on_site', 'deposit', 'prepay'])->mapWithKeys(fn ($m) => [$m => $t('payment_modes.'.$m)])->all()),
            MoneyInput::make('deposit')->label($t('deposit'))->visible(fn ($get) => $get('payment_mode') === 'deposit'),
            Toggle::make('is_active')->label($t('is_active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('reservations::admin.services.'.$key);

        return $table
            ->columns([
                TextColumn::make('name')->label($t('name'))->weight('medium'),
                TextColumn::make('duration_minutes')->label($t('duration'))->suffix(' min'),
                TextColumn::make('price')->label($t('price'))->formatStateUsing(fn (BookingService $record) => format_price($record->price, $record->currency)),
                TextColumn::make('resources.name')->label($t('resources'))->badge()->color('gray'),
                ToggleColumn::make('is_active')->label($t('is_active')),
            ])
            ->defaultSort('position')->reorderable('position')
            ->recordActions([
                EditAction::make()->mutateRecordDataUsing(fn (array $data, BookingService $record) => FillsContentTranslations::contentTranslationsForForm($record, $data)),
                DeleteAction::make(),
            ])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return ['index' => ManageServices::route('/')];
    }
}
