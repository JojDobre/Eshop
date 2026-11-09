<?php

namespace Modules\Forms\Filament\Resources\Submissions\Pages;

use Filament\Resources\Pages\ManageRecords;
use Modules\Forms\Filament\Resources\Submissions\FormSubmissionResource;

class ManageFormSubmissions extends ManageRecords
{
    protected static string $resource = FormSubmissionResource::class;
}
