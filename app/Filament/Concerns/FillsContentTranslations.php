<?php

namespace App\Filament\Concerns;

use App\Core\Content\MachineTranslator;
use App\Core\Content\ShopLocales;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

/**
 * Pre stránky úpravy záznamu s preložiteľnými stĺpcami.
 *
 * - Do formulára sa načíta text vo vybranom jazyku obsahu (predvolene jazyk obchodu).
 * - Pri viacjazyčnom obchode je v hlavičke prepínač jazyka (stránka ho pridá cez localeSwitcherActions()).
 *   Prepnutie najprv uloží zmeny v aktuálnom jazyku (s bežnou kontrolou formulára) a potom
 *   načíta texty v zvolenom jazyku.
 * - Jazyky, v ktorých chýba názov, sú v prepínači označené.
 */
trait FillsContentTranslations
{
    /** Jazyk, v ktorom sa práve upravujú preložiteľné polia. */
    public ?string $editingLocale = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->fillContentTranslations($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function fillContentTranslations(array $data): array
    {
        /** @var Model $record */
        $record = $this->getRecord();

        return self::contentTranslationsForForm($record, $data, $this->currentEditingLocale());
    }

    /**
     * Nahradí v dátach formulára preložiteľné stĺpce textom v jazyku obsahu (alebo v zadanom jazyku).
     * Použiteľné aj mimo stránok (napr. akcie a opakovače v relation manageroch).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function contentTranslationsForForm(Model $record, array $data, ?string $locale = null): array
    {
        if (method_exists($record, 'getTranslatableAttributes')) {
            foreach ($record->getTranslatableAttributes() as $attribute) {
                $data[$attribute] = $record->getTranslation($attribute, $locale ?? $record->getLocale(), false) ?: null;
            }
        }

        return $data;
    }

    public function currentEditingLocale(): string
    {
        return $this->editingLocale ??= app(ShopLocales::class)->default();
    }

    /**
     * Prepínač jazyka obsahu do hlavičky stránky (iba pri viacjazyčnom obchode).
     *
     * @return list<ActionGroup>
     */
    protected function localeSwitcherActions(): array
    {
        $locales = app(ShopLocales::class);

        if (! $locales->isMultilingual()) {
            return [];
        }

        $actions = [];

        // Popisky sa vyhodnotia pri vykreslení (akcie hlavičky si Filament pamätá od načítania stránky).
        foreach ($locales->enabled() as $locale) {
            $actions[] = Action::make('editLocale_'.$locale)
                ->label(fn () => $locales->label($locale).match (true) {
                    $this->translationMissing($locale) => ' – '.__('admin.translations.missing'),
                    (bool) $this->machineTranslator()?->needsReview($this->getRecord(), $locale) => ' – '.__('admin.translations.needs_review'),
                    default => '',
                })
                ->icon(fn () => $locale === $this->currentEditingLocale() ? Heroicon::Check : null)
                ->color(fn () => $this->translationMissing($locale) ? 'warning' : 'gray')
                ->action(fn () => $this->switchEditingLocale($locale));
        }

        return [
            ...$this->machineTranslationActions(),
            ActionGroup::make($actions)
                ->label(fn () => __('admin.translations.language', ['language' => $locales->label($this->currentEditingLocale())]))
                ->icon(Heroicon::OutlinedLanguage)
                ->color('gray')
                ->button(),
        ];
    }

    public function switchEditingLocale(string $locale): void
    {
        if ($locale === $this->currentEditingLocale() || ! app(ShopLocales::class)->isEnabled($locale)) {
            return;
        }

        // Uloží aktuálny jazyk; pri chybe vo formulári sa jazyk neprepne a chyby sa zobrazia.
        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);

        $this->editingLocale = $locale;
        $this->getRecord()->refresh();
        $this->fillForm();
    }

    /**
     * Uloženie preložiteľných polí do jazyka, ktorý sa práve upravuje.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->useContentLocale($this->currentEditingLocale());

        foreach ($this->translatableAttributes() as $attribute) {
            if (array_key_exists($attribute, $data) && blank($data[$attribute])) {
                // Prázdny preklad sa odstráni (zobrazí sa predvolený jazyk), nie uloží ako prázdny text.
                $record->forgetTranslation($attribute, $this->currentEditingLocale());
                unset($data[$attribute]);
            }
        }

        $record->fill($data);
        $record->save();
        $record->useContentLocale(null);

        // Uložením jazyka v administrácii je strojový preklad skontrolovaný.
        $this->machineTranslator()?->approve($record, $this->currentEditingLocale());

        return $record;
    }

    /**
     * Tlačidlá strojového prekladu (iba ak je zapnutý modul Prekladač a upravuje sa iný ako predvolený jazyk).
     *
     * @return list<Action>
     */
    protected function machineTranslationActions(): array
    {
        $translator = $this->machineTranslator();

        if ($translator === null) {
            return [];
        }

        $locales = app(ShopLocales::class);
        $isOtherLocale = fn () => $this->currentEditingLocale() !== $locales->default();

        return [
            Action::make('machineTranslate')
                ->label(fn () => __('admin.translations.translate_from', ['language' => $locales->label($locales->default())]))
                ->icon(Heroicon::OutlinedSparkles)
                ->color('gray')
                ->visible(fn () => $isOtherLocale() && $translator->isConfigured())
                ->requiresConfirmation()
                ->modalDescription(__('admin.translations.translate_confirm'))
                ->action(function () use ($translator, $locales): void {
                    try {
                        $characters = $translator->translateModel($this->getRecord(), $locales->default(), $this->currentEditingLocale());
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title(__('admin.translations.translate_failed'))->body($e->getMessage())->persistent()->send();

                        return;
                    }

                    $this->getRecord()->refresh();
                    $this->fillForm();
                    Notification::make()->success()->title(__('admin.translations.translated', ['count' => $characters]))->send();
                }),
            Action::make('approveTranslation')
                ->label(__('admin.translations.approve'))
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('warning')
                ->visible(fn () => $isOtherLocale() && $translator->needsReview($this->getRecord(), $this->currentEditingLocale()))
                ->action(function () use ($translator): void {
                    $translator->approve($this->getRecord(), $this->currentEditingLocale());
                    Notification::make()->success()->title(__('admin.translations.approved'))->send();
                }),
        ];
    }

    private function machineTranslator(): ?MachineTranslator
    {
        return app()->bound(MachineTranslator::class) ? app(MachineTranslator::class) : null;
    }

    /**
     * @return list<string>
     */
    private function translatableAttributes(): array
    {
        $record = $this->getRecord();

        return method_exists($record, 'getTranslatableAttributes') ? $record->getTranslatableAttributes() : [];
    }

    private function translationMissing(string $locale): bool
    {
        $record = $this->getRecord();

        if (! method_exists($record, 'getTranslation') || ! in_array('name', $this->translatableAttributes(), true)) {
            return false;
        }

        return blank($record->getTranslation('name', $locale, false));
    }
}
