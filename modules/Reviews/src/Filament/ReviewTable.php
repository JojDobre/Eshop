<?php

namespace Modules\Reviews\Filament;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Reviews\Models\Review;

/**
 * Spoločný formulár a tabuľka hodnotení (zoznam hodnotení aj záložka pri produkte).
 */
final class ReviewTable
{
    /**
     * @return list<Field>
     */
    public static function form(): array
    {
        $t = fn (string $key) => __('reviews::admin.'.$key);

        return [
            TextInput::make('author_name')->label($t('author'))->required()->maxLength(80),
            Select::make('rating')->label($t('rating'))->required()->options([5 => '★★★★★', 4 => '★★★★', 3 => '★★★', 2 => '★★', 1 => '★']),
            TextInput::make('title')->label($t('title'))->maxLength(120)->columnSpanFull(),
            Textarea::make('body')->label($t('body'))->rows(4)->maxLength(3000)->columnSpanFull(),
            Textarea::make('pros')->label($t('pros'))->rows(2)->maxLength(1000),
            Textarea::make('cons')->label($t('cons'))->rows(2)->maxLength(1000),
            Select::make('status')->label($t('status'))->required()->selectablePlaceholder(false)
                ->options(collect([Review::PENDING, Review::APPROVED, Review::REJECTED])->mapWithKeys(fn ($status) => [$status => $t('statuses.'.$status)])->all()),
            Textarea::make('reply')->label($t('reply'))->helperText($t('reply_help'))->rows(3)->maxLength(3000)->columnSpanFull(),
        ];
    }

    public static function table(Table $table, bool $withProduct = true): Table
    {
        $t = fn (string $key) => __('reviews::admin.'.$key);

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('product'))
            ->columns(array_values(array_filter([
                $withProduct ? TextColumn::make('product.name')->label($t('product'))->searchable()->limit(40) : null,
                TextColumn::make('rating')->label($t('rating'))->formatStateUsing(fn (int $state) => str_repeat('★', $state).str_repeat('☆', 5 - $state))->color('warning')->sortable(),
                TextColumn::make('title')->label($t('review'))->description(fn (Review $record) => str($record->body)->limit(120))->wrap()->placeholder('—'),
                TextColumn::make('author_name')->label($t('author'))->searchable(),
                IconColumn::make('order_id')->label($t('verified'))->boolean()->getStateUsing(fn (Review $record) => $record->isVerified()),
                TextColumn::make('status')->label($t('status'))->badge()
                    ->formatStateUsing(fn (string $state) => $t('statuses.'.$state))
                    ->color(fn (string $state) => match ($state) {
                        Review::APPROVED => 'success', Review::REJECTED => 'danger', default => 'warning'
                    }),
                TextColumn::make('created_at')->label($t('created_at'))->dateTime()->sortable(),
            ])))
            ->filters([
                SelectFilter::make('status')->label($t('status'))
                    ->options(collect([Review::PENDING, Review::APPROVED, Review::REJECTED])->mapWithKeys(fn ($status) => [$status => $t('statuses.'.$status)])->all()),
                TernaryFilter::make('verified')->label($t('verified'))
                    ->queries(true: fn (Builder $query) => $query->whereNotNull('order_id'), false: fn (Builder $query) => $query->whereNull('order_id')),
                SelectFilter::make('rating')->label($t('rating'))->options([5 => '5', 4 => '4', 3 => '3', 2 => '2', 1 => '1']),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('approve')->label($t('approve'))->icon(Heroicon::OutlinedCheck)->color('success')
                    ->visible(fn (Review $record) => $record->status !== Review::APPROVED)
                    ->action(fn (Review $record) => $record->update(['status' => Review::APPROVED])),
                Action::make('reject')->label($t('reject'))->icon(Heroicon::OutlinedXMark)->color('danger')
                    ->visible(fn (Review $record) => $record->status === Review::PENDING)
                    ->action(fn (Review $record) => $record->update(['status' => Review::REJECTED])),
                EditAction::make()->schema(self::form())
                    ->mutateDataUsing(fn (array $data, Review $record) => [...$data, 'replied_at' => filled($data['reply'] ?? null) && $data['reply'] !== $record->reply ? now() : $record->replied_at]),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkAction::make('approve_selected')->label($t('approve'))->icon(Heroicon::OutlinedCheck)
                    ->action(fn (Collection $records) => $records->each->update(['status' => Review::APPROVED])),
                BulkAction::make('reject_selected')->label($t('reject'))->icon(Heroicon::OutlinedXMark)->color('danger')
                    ->action(fn (Collection $records) => $records->each->update(['status' => Review::REJECTED])),
            ]);
    }
}
