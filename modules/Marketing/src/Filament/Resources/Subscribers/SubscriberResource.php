<?php

namespace Modules\Marketing\Filament\Resources\Subscribers;

use App\Filament\AdminGroup;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Modules\Marketing\Filament\Resources\Subscribers\Pages\ManageSubscribers;
use Modules\Marketing\Models\Subscriber;
use Modules\Marketing\Newsletter\Newsletter;
use UnitEnum;

class SubscriberResource extends Resource
{
    protected static ?string $model = Subscriber::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    protected static ?int $navigationSort = 40;

    protected static ?string $slug = 'newsletter-subscribers';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Sales;
    }

    public static function getModelLabel(): string
    {
        return __('marketing::admin.subscribers.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('marketing::admin.subscribers.plural');
    }

    public static function canAccess(): bool
    {
        return Newsletter::enabled() && (auth()->user()?->can('marketing.manage') ?? false);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('marketing::admin.subscribers.'.$key);
        $statuses = collect([Subscriber::PENDING, Subscriber::SUBSCRIBED, Subscriber::UNSUBSCRIBED])->mapWithKeys(fn ($status) => [$status => $t('statuses.'.$status)])->all();

        return $table
            ->columns([
                TextColumn::make('email')->label($t('email'))->searchable()->copyable(),
                TextColumn::make('name')->label($t('name'))->searchable()->placeholder('—'),
                TextColumn::make('status')->label($t('status'))->badge()->formatStateUsing(fn (string $state) => $statuses[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        Subscriber::SUBSCRIBED => 'success', Subscriber::UNSUBSCRIBED => 'gray', default => 'warning'
                    }),
                TextColumn::make('source')->label($t('source'))->formatStateUsing(fn (?string $state) => $state ? $t('sources.'.$state) : '—')->toggleable(),
                TextColumn::make('confirmed_at')->label($t('confirmed_at'))->dateTime()->placeholder('—')->sortable(),
                TextColumn::make('synced_at')->label($t('synced_at'))->since()->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([SelectFilter::make('status')->label($t('status'))->options($statuses)])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('unsubscribe')->label($t('unsubscribe'))->icon(Heroicon::OutlinedNoSymbol)->color('gray')->requiresConfirmation()
                    ->visible(fn (Subscriber $record) => $record->status !== Subscriber::UNSUBSCRIBED)
                    ->action(fn (Subscriber $record) => app(Newsletter::class)->unsubscribe($record)),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkAction::make('sync')->label($t('sync'))->icon(Heroicon::OutlinedArrowPath)
                    ->action(fn (Collection $records) => $records->each(fn (Subscriber $record) => app(Newsletter::class)->sync($record))),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageSubscribers::route('/')];
    }
}
