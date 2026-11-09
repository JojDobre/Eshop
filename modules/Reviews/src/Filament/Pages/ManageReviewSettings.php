<?php

namespace Modules\Reviews\Filament\Pages;

use App\Filament\Pages\Settings\BaseSettingsPage;
use App\Filament\Pages\Settings\ManageFeatures;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Reviews\ReviewSettings;

class ManageReviewSettings extends BaseSettingsPage
{
    protected static string $settings = ReviewSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?int $navigationSort = 61;

    protected static ?string $slug = 'settings/reviews';

    public static function getNavigationLabel(): string
    {
        return __('reviews::admin.plural');
    }

    public function getTitle(): string
    {
        return __('reviews::admin.settings_title');
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('reviews::admin.'.$key);

        return $schema->components([
            Section::make()->columns(2)->schema([
                Toggle::make('enabled')->label($t('enabled'))->columnSpanFull(),
                Toggle::make('require_approval')->label($t('require_approval'))->helperText($t('require_approval_help')),
                Toggle::make('verified_only')->label($t('verified_only'))->helperText($t('verified_only_help')),
                Toggle::make('request_enabled')->label($t('request_enabled'))->helperText($t('request_enabled_help'))->live(),
                TextInput::make('request_delay_days')->label($t('request_delay_days'))->required()->integer()->minValue(0)->maxValue(90)->suffix($t('days'))
                    ->visible(fn ($get) => (bool) $get('request_enabled')),
                Textarea::make('verification_info')->label($t('verification_info'))->helperText($t('verification_info_help'))
                    ->rows(3)->maxLength(1000)->columnSpanFull(),
            ]),
        ]);
    }

    /** V menu pod položkou „ManageFeatures“ (zobrazí sa po jej otvorení). */
    public static function getNavigationParentItem(): ?string
    {
        return ManageFeatures::getNavigationLabel();
    }
}
