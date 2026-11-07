<?php

namespace App\Filament\Resources\EmailTemplates\Pages;

use App\Core\Mail\EmailTemplateDefinition;
use App\Core\Mail\EmailTemplates;
use App\Filament\Concerns\FillsContentTranslations;
use App\Filament\Resources\EmailTemplates\EmailTemplateResource;
use App\Models\EmailTemplate;
use App\Settings\GeneralSettings;
use App\Settings\MailSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * @property EmailTemplate $record
 */
class EditEmailTemplate extends EditRecord
{
    // Prepínač jazyka a ukladanie do zvoleného jazyka (vlastné načítanie formulára je nižšie).
    use FillsContentTranslations;

    protected static string $resource = EmailTemplateResource::class;

    /**
     * Prázdne polia sa vo formulári predvyplnia predvoleným textom, aby ho obchodník mohol rovno upraviť.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $locale = $this->currentEditingLocale();
        $data['subject'] = $this->record->getTranslation('subject', $locale, false) ?: null;
        $data['body'] = $this->record->getTranslation('body', $locale, false) ?: $this->definition()->defaultBody($locale);

        return $data;
    }

    /**
     * Text zhodný s predvoleným sa neukladá, aby sa pri aktualizácii prekladov použil nový predvolený text.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $normalize = fn (?string $text) => trim(str_replace("\r\n", "\n", (string) $text));

        $locale = $this->currentEditingLocale();

        if ($normalize($data['body'] ?? null) === $normalize($this->definition()->defaultBody($locale))) {
            $data['body'] = null;
        }

        if ($normalize($data['subject'] ?? null) === '' || $normalize($data['subject']) === $normalize($this->definition()->defaultSubject($locale))) {
            $data['subject'] = null;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        $t = fn (string $key) => __('admin.email_templates.'.$key);

        return [
            ...$this->localeSwitcherActions(),
            Action::make('preview')
                ->label($t('preview'))
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->modalHeading(fn () => $this->renderSample()['subject'])
                ->modalContent(fn (): View => view('filament.email-templates.preview', ['html' => $this->renderHtml()]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel($t('close'))
                ->modalWidth('3xl'),
            Action::make('sendTest')
                ->label($t('send_test'))
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('gray')
                ->schema([
                    TextInput::make('email')->label($t('test_recipient'))->email()->required()->default(fn () => auth()->user()?->email),
                ])
                ->action(function (array $data) use ($t): void {
                    app(MailSettings::class)->applyToConfig(app(GeneralSettings::class));
                    $rendered = $this->renderSample();
                    $html = $this->renderHtml();

                    try {
                        Mail::html($html, fn ($message) => $message->to($data['email'])->subject('[TEST] '.$rendered['subject']));
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title($t('test_failed'))->body($e->getMessage())->persistent()->send();

                        return;
                    }

                    Notification::make()->success()->title($t('test_sent'))->send();
                }),
            Action::make('reset')
                ->label($t('reset'))
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription($t('reset_confirm'))
                ->action(function () use ($t): void {
                    $locale = $this->currentEditingLocale();
                    $this->record->forgetTranslation('subject', $locale)->forgetTranslation('body', $locale)->save();
                    $this->fillForm();
                    Notification::make()->success()->title($t('reset_done'))->send();
                }),
        ];
    }

    private function definition(): EmailTemplateDefinition
    {
        return app(EmailTemplates::class)->definition($this->record->key)
            ?? abort(404);
    }

    /**
     * Neuložený text z formulára s ukážkovými hodnotami premenných.
     *
     * @return array{subject: string, body: string}
     */
    private function renderSample(): array
    {
        $state = $this->form->getRawState();
        $definition = $this->definition();

        return app(EmailTemplates::class)->fill(
            (string) (($state['subject'] ?? null) ?: $definition->defaultSubject($this->currentEditingLocale())),
            (string) (($state['body'] ?? null) ?: $definition->defaultBody($this->currentEditingLocale())),
            $definition->sample,
            array_keys($definition->sample), // ukážkové hodnoty sú dôveryhodné (z kódu modulu)
        );
    }

    private function renderHtml(): string
    {
        return (string) app(Markdown::class)->render('mail.template', ['body' => $this->renderSample()['body']]);
    }
}
