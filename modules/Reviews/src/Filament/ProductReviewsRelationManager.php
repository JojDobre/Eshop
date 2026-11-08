<?php

namespace Modules\Reviews\Filament;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Reviews\ReviewSettings;

/**
 * Hodnotenia pri produkte (záložka v úprave produktu).
 */
class ProductReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'reviews';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('reviews::admin.plural');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return ReviewSettings::isEnabled();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components(ReviewTable::form());
    }

    public function table(Table $table): Table
    {
        return ReviewTable::table($table, withProduct: false);
    }
}
