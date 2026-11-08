<?php

namespace Modules\Marketing\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Odberateľ newslettera.
 *
 * @property int $id
 * @property string $email
 * @property string|null $name
 * @property string|null $locale
 * @property string $status
 * @property string $token
 * @property string|null $source
 */
class Subscriber extends Model
{
    public const PENDING = 'pending';

    public const SUBSCRIBED = 'subscribed';

    public const UNSUBSCRIBED = 'unsubscribed';

    protected $table = 'newsletter_subscribers';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime', 'unsubscribed_at' => 'datetime', 'synced_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (Subscriber $subscriber): void {
            $subscriber->token ??= Str::random(48);
            $subscriber->email = Str::lower(trim($subscriber->email));
        });
    }

    /**
     * @param  Builder<Subscriber>  $query
     */
    public function scopeSubscribed(Builder $query): void
    {
        $query->where('status', self::SUBSCRIBED);
    }

    public function confirmUrl(): string
    {
        return route('newsletter.confirm', $this->token);
    }

    public function unsubscribeUrl(): string
    {
        return route('newsletter.unsubscribe', $this->token);
    }
}
