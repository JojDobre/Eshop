<?php

namespace Tests\Feature\Shop;

use App\Core\Mail\TemplateMail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Modules\Forms\DefaultForm;
use Modules\Forms\Filament\Resources\Forms\Pages\CreateForm;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormSubmission;
use Modules\Pages\Models\Page;

class ContactFormTest extends ShopTestCase
{
    private function token(int $secondsAgo = 10): string
    {
        return Crypt::encryptString((string) (time() - $secondsAgo));
    }

    public function test_default_contact_form_is_on_contact_page_and_can_be_submitted(): void
    {
        Mail::fake();
        // Formulár vznikol pri inštalácii a je vložený na stránke Kontakt.
        $page = Page::query()->where('role', Page::ROLE_CONTACT)->sole();
        $form = Form::query()->sole();
        $this->assertNull(DefaultForm::create());

        $this->get($page->url())
            ->assertOk()
            ->assertSee('form-'.$form->id, false)
            ->assertSee('Odoslať správu');

        // Chýbajúce povinné polia.
        $this->from($page->url())->post(route('forms.submit', $form), ['form_token' => $this->token()])
            ->assertSessionHasErrorsIn('form'.$form->id, ['f0', 'f1', 'f3']);

        $this->from($page->url())->post(route('forms.submit', $form), [
            'form_token' => $this->token(), 'f0' => 'Jana', 'f1' => 'jana@example.com', 'f3' => 'Máte čerstvé vajcia?',
        ])->assertRedirect($page->url().'#form-'.$form->id)->assertSessionHas('form_sent_'.$form->id);

        $submission = FormSubmission::query()->sole();
        $this->assertSame('jana@example.com', $submission->email);
        $this->assertSame(['label' => 'Správa', 'value' => 'Máte čerstvé vajcia?'], $submission->data[3]);
        Mail::assertSent(TemplateMail::class, fn (TemplateMail $mail) => $mail->templateKey === 'forms.submission' && $mail->hasReplyTo('jana@example.com'));
    }

    public function test_bots_and_inactive_forms_are_rejected(): void
    {
        Mail::fake();
        $form = Form::query()->create(['name' => ['sk' => 'Dopyt'], 'fields' => ['sk' => [['label' => 'Otázka', 'type' => 'text', 'required' => true]]]]);

        $this->post(route('forms.submit', $form), ['form_token' => $this->token(), 'f0' => 'Ahoj', 'website' => 'spam.example'])->assertSessionHas('form_sent_'.$form->id);
        $this->post(route('forms.submit', $form), ['form_token' => $this->token(0), 'f0' => 'Ahoj'])->assertSessionHas('form_sent_'.$form->id);
        $this->assertSame(0, FormSubmission::query()->count());
        Mail::assertNothingSent();

        $form->update(['is_active' => false]);
        $this->post(route('forms.submit', $form), ['form_token' => $this->token(), 'f0' => 'Ahoj'])->assertNotFound();
    }

    public function test_admin_can_create_form_and_read_messages(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(CreateForm::class)
            ->fillForm(['name' => 'Objednávka služby', 'fields' => [['label' => 'Termín', 'type' => 'date', 'width' => 'full', 'required' => true]]])
            ->call('create')
            ->assertHasNoFormErrors();

        $form = Form::query()->where('name->sk', 'Objednávka služby')->firstOrFail();
        $this->assertSame('date', $form->formFields()[0]['type']);

        $form->submissions()->create(['data' => [['label' => 'Termín', 'value' => '2026-12-01']]]);
        $this->get('/admin/forms')->assertOk()->assertSee('Objednávka služby');
        $this->get('/admin/form-submissions')->assertOk()->assertSee('2026-12-01');
    }
}
