<?php

namespace Modules\Blog\Filament\Resources\Comments\Pages;

use Filament\Resources\Pages\ManageRecords;
use Modules\Blog\Filament\Resources\Comments\CommentResource;

class ManageComments extends ManageRecords
{
    protected static string $resource = CommentResource::class;
}
