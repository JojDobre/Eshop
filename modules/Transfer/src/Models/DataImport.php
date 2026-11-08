<?php

namespace Modules\Transfer\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $entity
 * @property string $file_path
 * @property string $file_name
 * @property array<string, string>|null $mapping stĺpec súboru → pole
 * @property string $status mapping, running, done, failed
 * @property int $total_rows
 * @property int $processed_rows
 * @property int $created_count
 * @property int $updated_count
 * @property list<string>|null $errors
 */
class DataImport extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['mapping' => 'array', 'errors' => 'array'];
    }
}
