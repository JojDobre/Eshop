<?php

namespace Modules\Marketing\Filament\Pages;

use App\Filament\Pages\Settings\BaseSettingsPage;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Marketing\Http\Controllers\FeedController;
use Modules\Marketing\MarketingSettings;
use Modules\Marketing\Tracking\TrackingCodes;

class ManageMarketingSettings extends BaseSettingsPage
{
    protected static string $settings = MarketingSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?int $navigationSort = 63;

    protected static ?string $slug = 'settings/marketing';

    public static function getNavigationLabel(): string
    {
        return __('marketing::admin.nav');
    }

    public function getTitle(): string
    {
        return __('marketing::admin.nav');
    }

    protected function afterSave(): void
    {
        // Feedy sa vygenerujú znova s novými nastaveniami.
        FeedController::flush();
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key, array $replace = []) => __('marketing::admin.'.$key, $replace);
        $id = fn (string $key) => 'regex:'.TrackingCodes::ID_PATTERNS[$key];

        return $schema->components([
            Section::make($t('newsletter.section'))->columns(2)->schema([
                Toggle::make('newsletter_enabled')->label($t('newsletter.enabled'))->helperText($t('newsletter.enabled_help'))->live(),
                Toggle::make('newsletter_checkout')->label($t('newsletter.checkout'))->visible(fn ($get) => (bool) $get('newsletter_enabled')),
                Select::make('newsletter_provider')->label($t('newsletter.provider'))->selectablePlaceholder(false)->live()
                    ->options(['none' => $t('newsletter.provider_none'), 'mailchimp' => 'Mailchimp', 'ecomail' => 'Ecomail'])
                    ->visible(fn ($get) => (bool) $get('newsletter_enabled')),
                TextInput::make('mailchimp_api_key')->label($t('newsletter.api_key'))->password()->revealable()->maxLength(100)
                    ->visible(fn ($get) => $get('newsletter_enabled') && $get('newsletter_provider') === 'mailchimp'),
                TextInput::make('mailchimp_list_id')->label($t('newsletter.mailchimp_list'))->maxLength(40)
                    ->visible(fn ($get) => $get('newsletter_enabled') && $get('newsletter_provider') === 'mailchimp'),
                TextInput::make('ecomail_api_key')->label($t('newsletter.api_key'))->password()->revealable()->maxLength(100)
                    ->visible(fn ($get) => $get('newsletter_enabled') && $get('newsletter_provider') === 'ecomail'),
                TextInput::make('ecomail_list_id')->label($t('newsletter.ecomail_list'))->maxLength(20)
                    ->visible(fn ($get) => $get('newsletter_enabled') && $get('newsletter_provider') === 'ecomail'),
            ]),
            Section::make($t('abandoned.section'))->description($t('abandoned.help'))->columns(2)->schema([
                Toggle::make('abandoned_enabled')->label($t('abandoned.enabled'))->live(),
                TextInput::make('abandoned_delay_hours')->label($t('abandoned.delay'))->required()->integer()->minValue(1)->maxValue(72)->suffix($t('abandoned.hours'))
                    ->visible(fn ($get) => (bool) $get('abandoned_enabled')),
            ]),
            Section::make($t('feeds.section'))->columns(2)->schema([
                Toggle::make('feeds_enabled')->label($t('feeds.enabled'))->live(),
                Toggle::make('feeds_out_of_stock')->label($t('feeds.out_of_stock'))->visible(fn ($get) => (bool) $get('feeds_enabled')),
                Text::make(fn () => $t('feeds.urls', ['heureka' => route('marketing.feed', 'heureka'), 'google' => route('marketing.feed', 'google'), 'facebook' => route('marketing.feed', 'facebook')]))
                    ->columnSpanFull()->visible(fn ($get) => (bool) $get('feeds_enabled')),
            ]),
            Section::make($t('tracking.section'))->description($t('tracking.help'))->columns(2)->schema([
                TextInput::make('ga4_id')->label($t('tracking.ga4'))->placeholder('G-XXXXXXXXXX')->rule($id('ga4_id'))->maxLength(30),
                TextInput::make('meta_pixel_id')->label($t('tracking.pixel'))->placeholder('123456789012345')->rule($id('meta_pixel_id'))->maxLength(30),
                TextInput::make('google_ads_id')->label($t('tracking.ads'))->placeholder('AW-123456789')->rule($id('google_ads_id'))->maxLength(30),
                TextInput::make('google_ads_label')->label($t('tracking.ads_label'))->rule($id('google_ads_label'))->maxLength(40),
            ]),
        ]);
    }

    /** V menu pod položkou „ManageFeatures“ (zobrazí sa po jej otvorení). */
    public static function getNavigationParentItem(): ?string
    {
        return \App\Filament\Pages\Settings\ManageFeatures::getNavigationLabel();
    }
}
