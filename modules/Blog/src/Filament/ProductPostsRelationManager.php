<?php

namespace Modules\Blog\Filament;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Blog\Models\Post;
use Modules\Catalog\Models\Product;

/**
 * Súvisiace články pri produkte (to isté prepojenie ako „odporúčané produkty“ v článku).
 */
class ProductPostsRelationManager extends RelationManager
{
    protected static string $relationship = 'blogPosts';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('blog::admin.related_posts');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('blog::admin.name')),
                TextColumn::make('published_at')->label(__('blog::admin.published_at'))->dateTime()->placeholder('—'),
            ])
            ->headerActions([
                AttachAction::make()->recordTitle(fn (Post $record) => $record->name)
                    ->recordSelectOptionsQuery(fn ($query) => $query->latest('id'))
                    ->recordSelectSearchColumns(['name'])
                    ->preloadRecordSelect(),
            ])
            ->recordActions([DetachAction::make()]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Product && (auth()->user()?->can('blog.manage') ?? false);
    }
}
