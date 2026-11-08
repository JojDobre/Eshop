<?php

namespace Modules\Blog\Filament\Pages;

use App\Filament\Pages\Settings\BaseSettingsPage;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Blog\BlogSettings;

class ManageBlogSettings extends BaseSettingsPage
{
    protected static string $settings = BlogSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?int $navigationSort = 60;

    protected static ?string $slug = 'settings/blog';

    public static function getNavigationLabel(): string
    {
        return __('blog::admin.nav');
    }

    public function getTitle(): string
    {
        return __('blog::admin.settings_title');
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('blog::admin.'.$key);

        return $schema->components([
            Section::make()->columns(2)->schema([
                Toggle::make('comments_enabled')->label($t('comments_on'))->helperText($t('comments_on_help')),
                Toggle::make('comments_require_approval')->label($t('comments_approval'))->helperText($t('comments_approval_help')),
                TextInput::make('posts_per_page')->label($t('posts_per_page'))->integer()->minValue(1)->maxValue(60)->required(),
            ]),
        ]);
    }
}
