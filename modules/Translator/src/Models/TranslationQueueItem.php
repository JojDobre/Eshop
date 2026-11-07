<?php

namespace Modules\Translator\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TranslationQueueItem extends Model
{
    public const PENDING = 'pending';

    public const DONE = 'done';

    public const FAILED = 'failed';

    protected $table = 'translation_queue';

    protected $guarded = [];

    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }
}
