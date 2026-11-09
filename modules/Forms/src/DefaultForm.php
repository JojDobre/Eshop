<?php

namespace Modules\Forms;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Forms\Models\Form;

/**
 * Predvolený formulár „Kontakt“ (po inštalácii aj pri aktualizácii) a jeho blok na stránke Kontakt.
 */
final class DefaultForm
{
    private const TEXTS = [
        'sk' => ['Kontakt', 'Meno', 'E-mail', 'Telefón', 'Správa', 'Odoslať správu', 'Ďakujeme, správu sme dostali. Ozveme sa vám čo najskôr.'],
        'cs' => ['Kontakt', 'Jméno', 'E-mail', 'Telefon', 'Zpráva', 'Odeslat zprávu', 'Děkujeme, zprávu jsme obdrželi. Ozveme se vám co nejdříve.'],
        'en' => ['Contact', 'Name', 'E-mail', 'Phone', 'Message', 'Send message', 'Thank you, we have received your message and will get back to you soon.'],
    ];

    public static function create(): ?Form
    {
        if (Form::query()->exists()) {
            return null;
        }

        $translations = fn (int $index) => collect(self::TEXTS)->map(fn (array $texts) => $texts[$index])->all();
        $fields = collect(self::TEXTS)->map(fn (array $texts) => [
            (string) Str::uuid() => ['label' => $texts[1], 'type' => 'text', 'required' => true, 'width' => 'half'],
            (string) Str::uuid() => ['label' => $texts[2], 'type' => 'email', 'required' => true, 'width' => 'half'],
            (string) Str::uuid() => ['label' => $texts[3], 'type' => 'tel', 'required' => false, 'width' => 'full'],
            (string) Str::uuid() => ['label' => $texts[4], 'type' => 'textarea', 'required' => true, 'width' => 'full'],
        ])->all();

        $form = Form::query()->create([
            'name' => $translations(0),
            'fields' => $fields,
            'submit_label' => $translations(5),
            'success_message' => $translations(6),
        ]);

        self::attachToContactPage($form);

        return $form;
    }

    /** Pridá blok formulára na stránku Kontakt (ak ešte nemá vlastné bloky). */
    private static function attachToContactPage(Form $form): void
    {
        if (! Schema::hasTable('pages') || ! Schema::hasColumn('pages', 'blocks')) {
            return;
        }

        $page = DB::table('pages')->where('role', 'contact')->first();

        if ($page === null || ! in_array(json_decode((string) $page->blocks, true), [null, []], true)) {
            return;
        }

        $block = [(string) Str::uuid() => ['type' => 'form', 'data' => ['form_id' => $form->id]]];
        $blocks = collect(array_keys((array) json_decode((string) $page->name, true)))->mapWithKeys(fn (string $locale) => [$locale => $block])->all();

        DB::table('pages')->where('id', $page->id)->update(['blocks' => json_encode($blocks)]);
    }
}
