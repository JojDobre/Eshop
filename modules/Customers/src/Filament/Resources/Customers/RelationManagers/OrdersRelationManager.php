<?php

namespace Modules\Customers\Filament\Resources\Customers\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Orders\Filament\Resources\Orders\OrderResource;
use Modules\Orders\Models\Order;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('customers::admin.orders');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        $t = fn (string $key) => __('customers::admin.'.$key);

        return $table
            ->recordTitleAttribute('number')
            ->columns([
                TextColumn::make('number')->label($t('order_number'))->weight('medium'),
                TextColumn::make('created_at')->label($t('date'))->dateTime(),
                TextColumn::make('status')->label($t('status'))->badge(),
                TextColumn::make('total')->label($t('total'))->alignEnd()
                    ->formatStateUsing(fn (Order $record) => format_price($record->total, $record->currency)),
            ])
            ->recordActions([
                Action::make('open')->label(__('filament-actions::view.single.label'))->url(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record])),
            ])
            ->recordUrl(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record]));
    }
}
