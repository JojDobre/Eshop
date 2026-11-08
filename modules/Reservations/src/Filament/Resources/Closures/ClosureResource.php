<?php

namespace Modules\Reservations\Filament\Resources\Closures;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Reservations\Filament\ReservationsNav;
use Modules\Reservations\Filament\Resources\Closures\Pages\ManageClosures;
use Modules\Reservations\Models\BookingClosure;

/**
 * Sviatky (pre všetkých) a dovolenky (pre jedného zamestnanca či miestnosť).
 */
class ClosureResource extends Resource
{
    use ReservationsNav;

    protected static ?string $model = BookingClosure::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 53;

    protected static ?string $slug = 'reservation-closures';

    public static function getModelLabel(): string
    {
        return __('reservations::admin.closures.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('reservations::admin.closures.plural');
    }

    public static function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('reservations::admin.closures.'.$key);

        return $schema->columns(2)->components([
            DatePicker::make('from_date')->label($t('from'))->required()->native(false),
            DatePicker::make('to_date')->label($t('to'))->required()->native(false)->afterOrEqual('from_date'),
            Select::make('booking_resource_id')->label($t('resource'))->relationship('resource', 'name')->placeholder($t('everyone')),
            TextInput::make('note')->label($t('note'))->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('reservations::admin.closures.'.$key);

        return $table
            ->columns([
                TextColumn::make('from_date')->label($t('from'))->date()->sortable(),
                TextColumn::make('to_date')->label($t('to'))->date(),
                TextColumn::make('resource.name')->label($t('resource'))->placeholder($t('everyone')),
                TextColumn::make('note')->label($t('note'))->placeholder('—'),
            ])
            ->defaultSort('from_date', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageClosures::route('/')];
    }
}
