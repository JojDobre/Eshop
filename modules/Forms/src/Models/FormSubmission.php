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
 * @property list<array{label: string, value: string}> $data
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

    /** Krátky výťah pre prehľad správ. */
    public function summary(): string
    {
        return collect($this->data)->pluck('value')->filter()->implode(' · ');
    }
}
