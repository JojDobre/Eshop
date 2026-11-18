<?php

namespace Modules\Reviews\Filament\Resources\Reviews\Pages;

use App\Filament\Concerns\HasStatusTabs;
use Filament\Resources\Pages\ManageRecords;
use Modules\Reviews\Filament\Resources\Reviews\ReviewResource;
use Modules\Reviews\Models\Review;

class ManageReviews extends ManageRecords
{
    use HasStatusTabs;

    protected static string $resource = ReviewResource::class;

    protected function statusTabs(): array
    {
        return self::columnTabs('status', [Review::PENDING, Review::APPROVED, Review::REJECTED], fn (string $status) => __('reviews::admin.statuses.'.$status));
    }
}
