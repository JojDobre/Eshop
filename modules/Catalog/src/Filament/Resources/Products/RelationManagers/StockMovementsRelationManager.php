<?php

namespace Modules\Catalog\Filament\Resources\Products\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * História skladu produktu (iba na čítanie – zmeny sa robia akciou „Upraviť sklad").
 */
class StockMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockMovements';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('catalog::admin.stock.history');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        $t = fn (string $key) => __('catalog::admin.stock.'.$key);

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                TextColumn::make('created_at')->label($t('date'))->dateTime(),
                TextColumn::make('type')->label($t('type'))->badge()->color('gray')
                    ->formatStateUsing(fn (string $state) => __('catalog::admin.stock.types.'.$state)),
                TextColumn::make('quantity')->label($t('change'))
                    ->formatStateUsing(fn (int $state) => ($state > 0 ? '+' : '').$state)
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'danger'),
                TextColumn::make('stock_after')->label($t('after')),
                TextColumn::make('reference')->label($t('reference'))->placeholder('—'),
                TextColumn::make('note')->label($t('note'))->placeholder('—')->wrap(),
                TextColumn::make('user.name')->label($t('user'))->placeholder(__('orders::admin.history.system')),
            ])
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) $ownerRecord->getAttribute('track_stock');
    }
}
