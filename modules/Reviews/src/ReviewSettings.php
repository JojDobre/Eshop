<?php

namespace Modules\Reviews;

use Spatie\LaravelSettings\Settings;

class ReviewSettings extends Settings
{
    public bool $enabled;

    public bool $require_approval;

    public bool $verified_only;

    public bool $request_enabled;

    public int $request_delay_days;

    public ?string $verification_info;

    public static function group(): string
    {
        return 'reviews';
    }

    public static function isEnabled(): bool
    {
        try {
            return app(self::class)->enabled;
        } catch (\Throwable) {
            return false;
        }
    }
}
