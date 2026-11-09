<?php

namespace Modules\Forms;

use App\Core\Mail\TemplateMail;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormSubmission;
use Throwable;

/**
 * Overenie a uloženie odoslaného formulára a upozornenie e-mailom.
 */
class SubmitForm
{
    /**
     * Pravidlá overenia podľa polí formulára.
     *
     * @return array<string, mixed>
     */
    public function rules(Form $form): array
    {
        $rules = [];

        foreach ($form->formFields() as $field) {
            $rule = [$field['required'] ? 'required' : 'nullable'];
            $rules[$field['key']] = [...$rule, ...match ($field['type']) {
                'email' => ['email', 'max:255'],
                'textarea' => ['string', 'max:5000'],
                'select' => [Rule::in($field['options'])],
                'checkbox' => $field['required'] ? ['accepted'] : ['boolean'],
                'date' => ['date'],
                'number' => ['numeric'],
                default => ['string', 'max:255'],
            }];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(Form $form): array
    {
        return collect($form->formFields())->mapWithKeys(fn (array $field) => [$field['key'] => $field['label']])->all();
    }

    /**
     * @param  array<string, mixed>  $input  Overené údaje.
     */
    public function handle(Form $form, array $input, ?string $ip = null): FormSubmission
    {
        $data = [];
        $email = null;

        foreach ($form->formFields() as $field) {
            $value = $input[$field['key']] ?? null;
            $value = $field['type'] === 'checkbox' ? (filter_var($value, FILTER_VALIDATE_BOOL) ? __('forms::shop.yes') : __('forms::shop.no')) : trim((string) $value);
            $data[] = ['label' => $field['label'], 'value' => $value];

            if ($field['type'] === 'email' && $email === null && $value !== '') {
                $email = $value;
            }
        }

        $submission = $form->submissions()->create([
            'data' => $data,
            'email' => $email,
            'locale' => app()->getLocale(),
            'ip_address' => $ip,
        ]);

        $this->notify($form, $submission);

        return $submission;
    }

    private function notify(Form $form, FormSubmission $submission): void
    {
        $recipient = $form->recipient ?: app(GeneralSettings::class)->contact_email;

        if (blank($recipient)) {
            return;
        }

        try {
            $mail = new TemplateMail('forms.submission', [
                'form_name' => $form->name,
                'fields' => collect($submission->data)->map(fn (array $row) => $row['label'].': '.$row['value'])->implode("\n\n"),
                'admin_url' => url('/admin/form-submissions'),
                'shop_name' => app(GeneralSettings::class)->shop_name,
            ]);

            if ($submission->email) {
                $mail->replyTo($submission->email);
            }

            Mail::to($recipient)->send($mail);
        } catch (Throwable $e) {
            // Správa je uložená v administrácii aj vtedy, keď sa e-mail nepodarí odoslať.
            report($e);
        }
    }
}
