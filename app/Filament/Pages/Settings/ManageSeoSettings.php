<?php

namespace App\Filament\Pages\Settings;

use App\Settings\SeoSettings;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageSeoSettings extends BaseSettingsPage
{
    protected static string $settings = SeoSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?int $navigationSort = 64;

    protected static ?string $slug = 'settings/seo';

    public static function getNavigationLabel(): string
    {
        return __('admin.seo.title');
    }

    public function getTitle(): string
    {
        return __('admin.seo.title');
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('admin.seo.'.$key);

        return $schema->components([
            Section::make($t('templates'))->description($t('templates_help'))->columns(2)->schema([
                TextInput::make('product_title')->label($t('product_title'))->required()->maxLength(160),
                TextInput::make('product_description')->label($t('product_description'))->maxLength(300),
                TextInput::make('category_title')->label($t('category_title'))->required()->maxLength(160),
                TextInput::make('category_description')->label($t('category_description'))->maxLength(300),
            ]),
            Section::make($t('indexing'))->columns(2)->schema([
                Toggle::make('auto_redirects')->label($t('auto_redirects'))->helperText($t('auto_redirects_help')),
                Toggle::make('noindex_filtered')->label($t('noindex_filtered'))->helperText($t('noindex_filtered_help')),
                Textarea::make('robots_extra')->label($t('robots_extra'))->helperText($t('robots_extra_help'))->rows(3)->maxLength(2000)->columnSpanFull(),
            ]),
        ]);
    }

    /** V menu pod položkou „ManageGeneralSettings“ (zobrazí sa po jej otvorení). */
    public static function getNavigationParentItem(): ?string
    {
        return \App\Filament\Pages\Settings\ManageGeneralSettings::getNavigationLabel();
    }
}
