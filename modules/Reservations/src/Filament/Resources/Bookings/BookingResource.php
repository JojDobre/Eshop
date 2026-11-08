<?php

namespace Modules\Reservations\Filament\Resources\Bookings;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Reservations\Bookings;
use Modules\Reservations\Filament\ReservationsNav;
use Modules\Reservations\Filament\Resources\Bookings\Pages\ListBookings;
use Modules\Reservations\Models\Booking;

class BookingResource extends Resource
{
    use ReservationsNav;

    protected static ?string $model = Booking::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?int $navigationSort = 50;

    protected static ?string $slug = 'reservations';

    protected static ?string $recordTitleAttribute = 'number';

    public static function getModelLabel(): string
    {
        return __('reservations::admin.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('reservations::admin.plural');
    }

    /** Počet rezervácií čakajúcich na platbu alebo potvrdenie. */
    public static function getNavigationBadge(): ?string
    {
        $pending = Booking::query()->where('status', Booking::PENDING)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        $t = fn (string $key) => __('reservations::admin.'.$key);

        return $schema->columns(2)->components([
            Section::make()->columns(2)->columnSpanFull()->schema([
                TextEntry::make('title')->label($t('service'))->state(fn (Booking $record) => $record->title()),
                TextEntry::make('resource.name')->label($t('resource'))->placeholder('—'),
                TextEntry::make('starts_at')->label($t('starts_at'))->dateTime('j. n. Y H:i'),
                TextEntry::make('ends_at')->label($t('ends_at'))->dateTime('j. n. Y H:i'),
                TextEntry::make('customer_name')->label($t('customer')),
                TextEntry::make('email')->label($t('email'))->copyable(),
                TextEntry::make('phone')->label($t('phone'))->placeholder('—'),
                TextEntry::make('amount_due')->label($t('amount_due'))->state(fn (Booking $record) => $record->amount_due > 0 ? format_price($record->amount_due, $record->currency).($record->is_paid ? ' ✓' : '') : '—'),
                TextEntry::make('note')->label($t('note'))->placeholder('—')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('reservations::admin.'.$key);
        $statuses = collect(Booking::STATUSES)->mapWithKeys(fn ($s) => [$s => $t('statuses.'.$s)])->all();

        return $table
            ->columns([
                TextColumn::make('number')->label($t('number'))->searchable()->weight('medium'),
                TextColumn::make('starts_at')->label($t('starts_at'))->dateTime('j. n. Y H:i')->sortable(),
                TextColumn::make('title')->label($t('service'))->state(fn (Booking $record) => $record->title()),
                TextColumn::make('resource.name')->label($t('resource'))->placeholder('—')->toggleable(),
                TextColumn::make('customer_name')->label($t('customer'))->searchable(['customer_name', 'email', 'phone'])
                    ->description(fn (Booking $record) => $record->email),
                TextColumn::make('status')->label($t('status'))->badge()->formatStateUsing(fn (string $state) => $t('statuses.'.$state))
                    ->color(fn (string $state) => match ($state) {
                        Booking::PENDING => 'warning', Booking::CONFIRMED => 'success', Booking::CANCELLED => 'danger', default => 'gray',
                    }),
                IconColumn::make('is_paid')->label($t('is_paid'))->boolean()->toggleable(),
            ])
            ->defaultSort('starts_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label($t('status'))->options($statuses),
                SelectFilter::make('booking_service_id')->label($t('service'))->relationship('service', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name),
                SelectFilter::make('booking_resource_id')->label($t('resource'))->relationship('resource', 'name'),
                Filter::make('upcoming')->label($t('upcoming'))->query(fn (Builder $query) => $query->where('starts_at', '>=', now()->startOfDay()))->default(),
            ])
            ->recordActions([
                ActionGroup::make(self::statusActions()),
            ]);
    }

    /**
     * Zmeny stavu rezervácie (zoznam aj detail).
     *
     * @return list<Action>
     */
    public static function statusActions(): array
    {
        $t = fn (string $key) => __('reservations::admin.'.$key);
        $done = fn () => Notification::make()->title($t('saved'))->success()->send();

        return [
            Action::make('markPaid')->label($t('mark_paid'))->icon(Heroicon::OutlinedBanknotes)
                ->visible(fn (Booking $record) => $record->amount_due > 0 && ! $record->is_paid && $record->status !== Booking::CANCELLED)
                ->requiresConfirmation()
                ->action(function (Booking $record) use ($done): void {
                    app(Bookings::class)->confirm($record, paid: true);
                    $done();
                }),
            Action::make('confirm')->label($t('confirm'))->icon(Heroicon::OutlinedCheck)
                ->visible(fn (Booking $record) => $record->status === Booking::PENDING)
                ->requiresConfirmation()
                ->action(function (Booking $record) use ($done): void {
                    app(Bookings::class)->confirm($record, paid: false);
                    $done();
                }),
            Action::make('complete')->label($t('complete'))->icon(Heroicon::OutlinedCheckBadge)
                ->visible(fn (Booking $record) => in_array($record->status, [Booking::PENDING, Booking::CONFIRMED], true))
                ->action(function (Booking $record) use ($done): void {
                    app(Bookings::class)->complete($record);
                    $done();
                }),
            Action::make('cancel')->label($t('cancel'))->icon(Heroicon::OutlinedXMark)->color('danger')
                ->visible(fn (Booking $record) => in_array($record->status, [Booking::PENDING, Booking::CONFIRMED], true))
                ->requiresConfirmation()
                ->action(function (Booking $record) use ($done): void {
                    app(Bookings::class)->cancel($record);
                    $done();
                }),
        ];
    }

    public static function getPages(): array
    {
        return ['index' => ListBookings::route('/')];
    }
}
