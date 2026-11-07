<?php

namespace Modules\Returns\Filament\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Returns\Filament\Resources\ReturnRequests\ReturnRequestResource;
use Modules\Returns\Models\ReturnRequest;

class ReturnRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'returnRequests';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('returns::admin.plural');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->returnRequests()->exists();
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        $t = fn (string $key) => __('returns::admin.'.$key);

        return $table
            ->columns([
                TextColumn::make('number')->label($t('number'))->weight('medium'),
                TextColumn::make('type')->label($t('type'))->badge(),
                TextColumn::make('status')->label($t('status'))->badge(),
                TextColumn::make('items')->label($t('items'))->state(fn (ReturnRequest $record) => $record->itemsSummary()),
                TextColumn::make('deadline_at')->label($t('deadline_at'))->date(),
            ])
            ->recordUrl(fn (ReturnRequest $record) => ReturnRequestResource::getUrl('view', ['record' => $record]))
            ->paginated(false);
    }
}
