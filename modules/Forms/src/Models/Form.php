<?php

namespace Modules\Forms\Models;

use App\Core\Content\HasContentTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Formulár (kontakt, dopyt…). Polia: list<array{key: string, label: string, type: string, required?: bool,
 * options?: string, placeholder?: string, width?: string}>.
 *
 * @property int $id
 * @property string $name
 * @property array<int, array<string, mixed>>|null $fields
 * @property string|null $submit_label
 * @property string|null $success_message
 * @property string|null $recipient
 * @property bool $is_active
 */
class Form extends Model
{
    use HasContentTranslations;

    public const TYPES = ['text', 'email', 'tel', 'textarea', 'select', 'checkbox', 'date', 'number'];

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['name', 'fields', 'submit_label', 'success_message'];

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    /**
     * Polia formulára v aktuálnom jazyku (s doplneným kľúčom a typom).
     *
     * @return list<array{key: string, label: string, type: string, required: bool, options: list<string>, placeholder: string, width: string}>
     */
    public function formFields(): array
    {
        $fields = [];

        foreach (array_values((array) $this->fields) as $index => $field) {
            $field = is_array($field) ? ($field['data'] ?? $field) : [];
            $label = trim((string) ($field['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $type = in_array($field['type'] ?? null, self::TYPES, true) ? $field['type'] : 'text';
            $fields[] = [
                'key' => 'f'.$index,
                'label' => $label,
                'type' => $type,
                'required' => (bool) ($field['required'] ?? false),
                'options' => array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($field['options'] ?? '')) ?: []), 'strlen')),
                'placeholder' => (string) ($field['placeholder'] ?? ''),
                'width' => ($field['width'] ?? 'full') === 'half' && $type !== 'textarea' ? 'half' : 'full',
            ];
        }

        return $fields;
    }
}
