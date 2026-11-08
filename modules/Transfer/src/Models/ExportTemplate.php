<?php

namespace Modules\Transfer\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property array<string, mixed> $config entity, format, columns, delimiter, encoding, period
 * @property string $schedule none, daily, weekly, monthly
 * @property string|null $email
 */
class ExportTemplate extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['config' => 'array', 'last_sent_at' => 'datetime'];
    }

    /** Má sa šablóna poslať teraz (podľa plánu a posledného odoslania)? */
    public function isDue(): bool
    {
        if ($this->schedule === 'none' || blank($this->email)) {
            return false;
        }

        $last = $this->last_sent_at;

        return match ($this->schedule) {
            'daily' => $last === null || $last->lt(now()->startOfDay()),
            'weekly' => $last === null || $last->lt(now()->startOfWeek()),
            'monthly' => $last === null || $last->lt(now()->startOfMonth()),
            default => false,
        };
    }
}
