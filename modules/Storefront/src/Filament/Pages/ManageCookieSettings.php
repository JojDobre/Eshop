<?php

namespace Modules\Storefront\Filament\Pages;

use App\Filament\Pages\Settings\BaseSettingsPage;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Storefront\Cookies\CookieSettings;

class ManageCookieSettings extends BaseSettingsPage
{
    protected static string $settings = CookieSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 60;

    protected static ?string $slug = 'settings/cookies';

    public static function getNavigationLabel(): string
    {
        return __('storefront::admin.cookies.title');
    }

    public function getTitle(): string
    {
        return __('storefront::admin.cookies.title');
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('storefront::admin.cookies.'.$key);

        return $schema->components([
            Section::make()
                ->description($t('intro'))
                ->schema([
                    Toggle::make('enabled')->label($t('enabled'))->helperText($t('enabled_help')),
                    Textarea::make('analytics_code')->label($t('analytics_code'))->helperText($t('analytics_code_help'))
                        ->rows(6)->extraInputAttributes(['class' => 'font-mono text-xs']),
                    Textarea::make('marketing_code')->label($t('marketing_code'))->helperText($t('marketing_code_help'))
                        ->rows(6)->extraInputAttributes(['class' => 'font-mono text-xs']),
                ]),
        ]);
    }

    /** V menu pod položkou „ManageGeneralSettings“ (zobrazí sa po jej otvorení). */
    public static function getNavigationParentItem(): ?string
    {
        return \App\Filament\Pages\Settings\ManageGeneralSettings::getNavigationLabel();
    }
}
