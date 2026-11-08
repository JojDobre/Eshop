<?php

namespace Modules\Reservations\Filament\Resources\BookingResources;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Modules\Reservations\Filament\ReservationsNav;
use Modules\Reservations\Filament\Resources\BookingResources\Pages\ManageBookingResources;
use Modules\Reservations\Models\BookingResource;

/**
 * Zamestnanci a miestnosti s týždenným rozvrhom (viac úsekov za deň = prestávky medzi nimi).
 */
class BookingResourceResource extends Resource
{
    use ReservationsNav;

    protected static ?string $model = BookingResource::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 52;

    protected static ?string $slug = 'reservation-resources';

    public static function getModelLabel(): string
    {
        return __('reservations::admin.resources.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('reservations::admin.resources.plural');
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('reservations::admin.resources.'.$key);
        $days = collect(range(1, 7))->map(fn (int $day) => Repeater::make('schedule.'.$day)->label(__('reservations::admin.days.'.$day))
            ->schema([
                TimePicker::make('0')->label($t('from'))->seconds(false)->required(),
                TimePicker::make('1')->label($t('to'))->seconds(false)->required()->after('0'),
            ])->columns(2)->defaultItems(0)->addActionLabel($t('add_hours'))->reorderable(false))->all();

        return $schema->columns(2)->components([
            TextInput::make('name')->label($t('name'))->required()->maxLength(120),
            Select::make('type')->label($t('type'))->selectablePlaceholder(false)->default('staff')
                ->options(['staff' => $t('types.staff'), 'room' => $t('types.room')]),
            Toggle::make('is_active')->label($t('is_active'))->default(true),
            Section::make($t('schedule'))->description($t('schedule_help'))->columns(2)->columnSpanFull()->schema($days),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('reservations::admin.resources.'.$key);

        return $table
            ->columns([
                TextColumn::make('name')->label($t('name'))->weight('medium'),
                TextColumn::make('type')->label($t('type'))->formatStateUsing(fn (string $state) => $t('types.'.$state))->badge()->color('gray'),
                TextColumn::make('services.name')->label(__('reservations::admin.services.plural'))->badge()->color('gray'),
                ToggleColumn::make('is_active')->label($t('is_active')),
            ])
            ->defaultSort('position')->reorderable('position')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return ['index' => ManageBookingResources::route('/')];
    }
}
