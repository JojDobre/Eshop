<?php

namespace Modules\Reviews\Filament\Resources\Reviews\Pages;

use Filament\Resources\Pages\ManageRecords;
use Modules\Reviews\Filament\Resources\Reviews\ReviewResource;

class ManageReviews extends ManageRecords
{
    protected static string $resource = ReviewResource::class;
}
