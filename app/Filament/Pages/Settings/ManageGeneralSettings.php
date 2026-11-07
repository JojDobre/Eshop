<?php

namespace App\Filament\Pages\Settings;

use App\Core\Content\ShopLocales;
use App\Core\Themes\Theme;
use App\Core\Themes\ThemeManager;
use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageGeneralSettings extends BaseSettingsPage
{
    protected static string $settings = GeneralSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'settings/general';

    public static function getNavigationLabel(): string
    {
        return __('admin.settings.general.title');
    }

    public function getTitle(): string
    {
        return __('admin.settings.general.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.settings.general.section_shop'))
                ->columns(2)
                ->schema([
                    TextInput::make('shop_name')
                        ->label(__('admin.settings.general.shop_name'))
                        ->required()
                        ->maxLength(120),
                    TextInput::make('tagline')
                        ->label(__('admin.settings.general.tagline'))
                        ->helperText(__('admin.settings.general.tagline_help'))
                        ->maxLength(200),
                    TextInput::make('contact_email')
                        ->label(__('admin.settings.general.contact_email'))
                        ->email()
                        ->required()
                        ->maxLength(255),
                    TextInput::make('contact_phone')
                        ->label(__('admin.settings.general.contact_phone'))
                        ->tel()
                        ->maxLength(40),
                ]),
            Section::make(__('admin.settings.general.section_regional'))
                ->columns(3)
                ->schema([
                    Select::make('default_locale')
                        ->label(__('admin.settings.general.default_locale'))
                        ->helperText(__('admin.settings.general.default_locale_help'))
                        ->options(config('eshop.content_locales'))
                        ->required()
                        ->live()
                        ->native(false),
                    Select::make('default_currency')
                        ->label(__('admin.settings.general.default_currency'))
                        ->options(collect(config('eshop.currencies'))->mapWithKeys(fn ($symbol, $code) => [$code => "{$code} ({$symbol})"]))
                        ->required()
                        ->native(false),
                    Select::make('country')
                        ->label(__('admin.settings.general.country'))
                        ->options(collect(config('eshop.countries'))->mapWithKeys(fn ($code) => [$code => __('countries.'.$code)]))
                        ->required()
                        ->native(false),
                    Toggle::make('locales_enabled')
                        ->label(__('admin.features.locales'))
                        ->helperText(__('admin.features.locales_help'))
                        ->live()
                        ->columnSpanFull(),
                    CheckboxList::make('locales')
                        ->label(__('admin.settings.general.locales'))
                        ->helperText(__('admin.settings.general.locales_help'))
                        ->options(fn (Get $get) => collect(config('eshop.content_locales'))->except($get('default_locale'))->all())
                        ->columns(4)
                        ->columnSpanFull()
                        ->visible(fn (Get $get) => (bool) $get('locales_enabled'))
                        ->dehydrateStateUsing(fn (?array $state, Get $get) => array_values(array_diff($state ?? [], [$get('default_locale')]))),
                ]),
            Section::make(__('admin.settings.general.section_theme'))
                ->schema([
                    Select::make('theme')
                        ->label(__('admin.settings.general.theme'))
                        ->options(fn () => collect(app(ThemeManager::class)->all())->map(fn (Theme $theme) => $theme->title()))
                        ->required()
                        ->native(false),
                ]),
        ]);
    }

    protected function afterSave(): void
    {
        app(ThemeManager::class)->flush();
        app(ShopLocales::class)->refresh();
    }
}
