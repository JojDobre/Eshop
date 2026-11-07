<?php

namespace Modules\Translator\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MachineTranslation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }
}
