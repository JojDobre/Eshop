<?php

namespace Modules\Forms\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;
use Modules\Forms\Models\Form;
use Modules\Forms\SubmitForm;
use Throwable;

class FormController extends Controller
{
    /** Najkratší čas medzi zobrazením formulára a odoslaním (sekundy) – ochrana pred robotmi. */
    public const MIN_SECONDS = 3;

    public function submit(Request $request, Form $form, SubmitForm $action): RedirectResponse
    {
        abort_unless($form->is_active, 404);

        $bag = 'form'.$form->id;
        $data = $request->validateWithBag($bag, $action->rules($form), [], $action->attributes($form));
        $back = redirect()->to(url()->previous().'#form-'.$form->id);

        // Robotovi sa tvárime, že správa prešla, ale neuložíme ju.
        if (! $this->isBot($request)) {
            $action->handle($form, $data, $request->ip());
        }

        return $back->with('form_sent_'.$form->id, $form->success_message ?: __('forms::shop.sent'));
    }

    private function isBot(Request $request): bool
    {
        if (filled($request->input('website'))) {
            return true;
        }

        try {
            return time() - (int) Crypt::decryptString((string) $request->input('form_token')) < self::MIN_SECONDS;
        } catch (Throwable) {
            return true;
        }
    }
}
