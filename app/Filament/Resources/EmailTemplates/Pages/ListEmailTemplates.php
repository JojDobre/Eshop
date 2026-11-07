<?php

namespace App\Filament\Resources\EmailTemplates\Pages;

use App\Core\Mail\EmailTemplates;
use App\Filament\Resources\EmailTemplates\EmailTemplateResource;
use Filament\Resources\Pages\ListRecords;

class ListEmailTemplates extends ListRecords
{
    protected static string $resource = EmailTemplateResource::class;

    public function mount(): void
    {
        // Záznamy pre e-maily, ktoré moduly zaregistrovali neskôr (napr. po zapnutí modulu).
        app(EmailTemplates::class)->syncRecords();

        parent::mount();
    }
}
