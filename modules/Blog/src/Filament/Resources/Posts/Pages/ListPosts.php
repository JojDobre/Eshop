<?php

namespace Modules\Blog\Filament\Resources\Posts\Pages;

use App\Filament\Concerns\HasStatusTabs;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Blog\Filament\Resources\Posts\PostResource;
use Modules\Blog\Models\Post;

class ListPosts extends ListRecords
{
    use HasStatusTabs;

    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    protected function statusTabs(): array
    {
        return self::columnTabs('status', [Post::PUBLISHED, Post::DRAFT], fn (string $status) => __('blog::admin.statuses.'.$status));
    }
}
