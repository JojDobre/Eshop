<?php

namespace Modules\Forms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Odoslaný formulár. Údaje sa ukladajú aj s popiskami polí, aby zostali čitateľné po úprave formulára.
 *
 * @property int $id
 * @property int $form_id
 * @property list<array{label: string, value: string, type?: string}> $data
 * @property string|null $email
 * @property Carbon|null $read_at
 */
class FormSubmission extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['data' => 'array', 'read_at' => 'datetime'];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * Polia správy s typom. Staršie správy typ nemajú – doplní sa z aktuálneho formulára podľa popisu poľa,
     * inak sa odhadne z hodnoty (e-mail, telefón, dlhý text).
     *
     * @return list<array{label: string, value: string, type: string}>
     */
    public function fields(): array
    {
        $types = collect($this->form?->formFields() ?? [])->mapWithKeys(fn (array $field) => [$field['label'] => $field['type']]);

        return collect($this->data ?? [])->map(function (array $row) use ($types): array {
            $value = (string) ($row['value'] ?? '');
            $type = $row['type'] ?? $types[$row['label'] ?? ''] ?? match (true) {
                $value !== '' && $value === $this->email => 'email',
                str_contains($value, "\n") || mb_strlen($value) > 120 => 'textarea',
                default => 'text',
            };

            return ['label' => (string) ($row['label'] ?? ''), 'value' => $value, 'type' => $type];
        })->all();
    }

    /** Meno odosielateľa: prvé vyplnené krátke textové pole (zvyčajne „Meno“). */
    public function senderName(): ?string
    {
        return collect($this->fields())->first(fn (array $field) => $field['type'] === 'text' && $field['value'] !== '' && mb_strlen($field['value']) <= 80)['value'] ?? null;
    }

    /** Úryvok pre zoznam správ: text správy (dlhé polia) bez mena a kontaktov, ktoré sú v stĺpci Odosielateľ. */
    public function preview(): string
    {
        $text = collect($this->fields())->where('type', 'textarea')->pluck('value')->filter()->implode(' ');

        return preg_replace('/\s+/u', ' ', $text !== '' ? $text : $this->summary());
    }

    /** Krátky výťah pre prehľad správ. */
    public function summary(): string
    {
        return collect($this->data)->pluck('value')->filter()->implode(' · ');
    }
}
