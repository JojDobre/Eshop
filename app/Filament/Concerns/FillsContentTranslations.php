<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Pre stránky úpravy záznamu s preložiteľnými stĺpcami: do formulára sa načíta text
 * v jazyku obsahu obchodu (nie pole všetkých prekladov).
 *
 * Ukladanie funguje bez úprav – model uloží hodnotu do jazyka obsahu (HasContentTranslations).
 */
trait FillsContentTranslations
{
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

        if (method_exists($record, 'getTranslatableAttributes')) {
            foreach ($record->getTranslatableAttributes() as $attribute) {
                $data[$attribute] = $record->getTranslation($attribute, $record->getLocale(), false) ?: null;
            }
        }

        return $data;
    }
}
