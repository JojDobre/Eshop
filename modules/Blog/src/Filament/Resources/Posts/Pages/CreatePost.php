<?php

namespace Modules\Blog\Filament\Resources\Posts\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Blog\Filament\Resources\Posts\PostResource;
use Modules\Blog\Models\Post;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    /**
     * Zverejnený článok bez dátumu sa zverejní hneď.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['status'] ?? null) === Post::PUBLISHED && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
