<?php

namespace Modules\Catalog\Filament\Resources\Categories\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Catalog\Filament\Resources\Categories\CategoryResource;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;
}
