<?php

namespace Modules\Forms\Filament\Resources\Submissions\Pages;

use App\Filament\Concerns\HasStatusTabs;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;
use Modules\Forms\Filament\Resources\Submissions\FormSubmissionResource;

class ManageFormSubmissions extends ManageRecords
{
    use HasStatusTabs;

    protected static string $resource = FormSubmissionResource::class;

    protected function statusTabs(): array
    {
        return [
            'unread' => [__('forms::admin.unread'), fn (Builder $query) => $query->whereNull('read_at')],
            'read' => [__('forms::admin.read'), fn (Builder $query) => $query->whereNotNull('read_at')],
        ];
    }
}
