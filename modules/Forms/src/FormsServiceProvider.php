<?php

namespace Modules\Forms;

use App\Core\Blocks\BlockType;
use App\Core\Blocks\BlockTypes;
use App\Core\Blocks\CoreBlocks;
use App\Core\Events\EshopInstalled;
use App\Core\Mail\EmailTemplateDefinition;
use App\Core\Mail\EmailTemplates;
use App\Core\Modules\ModuleServiceProvider;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Event;
use Modules\Forms\Models\Form;

class FormsServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'forms';
    }

    public function boot(): void
    {
        parent::boot();

        // Až po ostatných moduloch, aby už existovala stránka Kontakt (modul Stránky).
        $this->app->booted(fn () => Event::listen(EshopInstalled::class, fn () => DefaultForm::create()));

        $this->app->make(BlockTypes::class)->register(new BlockType('form', fn () => __('forms::admin.block'), fn () => [
            Select::make('form_id')->label(__('forms::admin.form'))->required()
                ->options(fn () => Form::query()->get()->mapWithKeys(fn (Form $form) => [$form->id => $form->name])->all()),
            TextInput::make('title')->label(__('admin.blocks.title'))->maxLength(160),
            TextInput::make('text')->label(__('admin.blocks.text'))->maxLength(500),
            CoreBlocks::hidden(),
        ], prepare: fn (array $data) => [...$data, 'form' => Form::query()->where('is_active', true)->find((int) ($data['form_id'] ?? 0))],
            icon: 'heroicon-o-envelope'));

        $this->app->make(EmailTemplates::class)->register(new EmailTemplateDefinition('forms.submission', 'forms::mail_templates.submission',
            ['form_name', 'fields', 'admin_url', 'shop_name'],
            sample: ['form_name' => 'Kontakt', 'fields' => "Meno: Jana Nováková\n\nE-mail: jana@example.com\n\nSpráva: Dobrý deň, máte na sklade…", 'admin_url' => url('/admin/form-submissions')]));
    }
}
