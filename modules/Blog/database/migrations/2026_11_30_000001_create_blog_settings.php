<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('blog.comments_enabled', true);
        $this->migrator->add('blog.comments_require_approval', true);
        $this->migrator->add('blog.posts_per_page', 12);
    }
};
