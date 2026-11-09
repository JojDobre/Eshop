<?php

namespace App\Filament\Pages\Settings;

use App\Settings\BrandingSettings;
use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageBrandingSettings extends BaseSettingsPage
{
    protected static string $settings = BrandingSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'settings/branding';

    /** Povolené formáty obrázkov. SVG zámerne chýba, kým nebude dostupné jeho bezpečné čistenie. */
    private const IMAGE_TYPES = ['image/png', 'image/jpeg', 'image/webp'];

    public static function getNavigationLabel(): string
    {
        return __('admin.settings.branding.title');
    }

    public function getTitle(): string
    {
        return __('admin.settings.branding.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.settings.branding.section_logo'))
                ->columns(3)
                ->schema([
                    $this->image('logo')
                        ->label(__('admin.settings.branding.logo'))
                        ->helperText(__('admin.settings.branding.logo_help')),
                    $this->image('logo_dark')
                        ->label(__('admin.settings.branding.logo_dark'))
                        ->helperText(__('admin.settings.branding.logo_dark_help')),
                    $this->image('favicon', ['image/png', 'image/x-icon', 'image/vnd.microsoft.icon'])
                        ->label(__('admin.settings.branding.favicon'))
                        ->helperText(__('admin.settings.branding.favicon_help'))
                        ->maxSize(512),
                ]),
            Section::make(__('admin.settings.branding.section_colors'))
                ->schema([
                    ColorPicker::make('primary_color')
                        ->label(__('admin.settings.branding.primary_color'))
                        ->helperText(__('admin.settings.branding.primary_color_help'))
                        ->hex()
                        ->regex('/^#[0-9a-fA-F]{6}$/')
                        ->required(),
                ]),
        ]);
    }

    /**
     * @param  list<string>|null  $types
     */
    private function image(string $name, ?array $types = null): FileUpload
    {
        return FileUpload::make($name)
            ->disk('public')
            ->directory('branding')
            ->visibility('public')
            ->image()
            ->acceptedFileTypes($types ?? self::IMAGE_TYPES)
            ->maxSize(2048);
    }

    /** V menu pod položkou „ManageGeneralSettings“ (zobrazí sa po jej otvorení). */
    public static function getNavigationParentItem(): ?string
    {
        return \App\Filament\Pages\Settings\ManageGeneralSettings::getNavigationLabel();
    }
}
