<?php

namespace Modules\Catalog\CustomFields;

use Illuminate\Database\Eloquent\Model;

/**
 * Pre stránky úpravy a vytvorenia: načítanie a uloženie vlastných polí (kľúč formulára „custom_fields“).
 * Preložiteľné polia sa ukladajú do jazyka, ktorý sa práve upravuje.
 */
trait HandlesCustomFields
{
    /** @var array<string, mixed>|null */
    private ?array $pendingCustomFieldValues = null;

    abstract protected function customFieldEntity(): string;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function withCustomFields(array $data, Model $record): array
    {
        $data['custom_fields'] = app(CustomFieldValues::class)->get($this->customFieldEntity(), (int) $record->getKey(), $this->customFieldLocale(), fallback: false);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function withoutCustomFields(array $data): array
    {
        $this->pendingCustomFieldValues = $data['custom_fields'] ?? null;
        unset($data['custom_fields']);

        return $data;
    }

    protected function saveCustomFields(Model $record): void
    {
        if ($this->pendingCustomFieldValues !== null) {
            app(CustomFieldValues::class)->save($this->customFieldEntity(), (int) $record->getKey(), $this->pendingCustomFieldValues, $this->customFieldLocale());
            $this->pendingCustomFieldValues = null;
        }
    }

    private function customFieldLocale(): ?string
    {
        return method_exists($this, 'currentEditingLocale') ? $this->currentEditingLocale() : null;
    }
}
