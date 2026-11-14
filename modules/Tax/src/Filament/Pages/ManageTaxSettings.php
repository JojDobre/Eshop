<?php

namespace Modules\Tax\Filament\Pages;

use App\Filament\Forms\FeatureNotice;
use App\Filament\Pages\Settings\BaseSettingsPage;
use App\Settings\TaxSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Tax\Actions\FillEuRates;

class ManageTaxSettings extends BaseSettingsPage
{
    protected static string $settings = TaxSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?int $navigationSort = 35;

    protected static ?string $slug = 'settings/tax';

    public static function getNavigationLabel(): string
    {
        return __('tax::admin.navigation');
    }

    public function getTitle(): string
    {
        return __('tax::admin.navigation');
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('tax::admin.settings.'.$key);

        return $schema->components([
            Section::make()->schema([
                Toggle::make('vat_payer')->label($t('vat_payer'))->helperText($t('vat_payer_help'))->live(),
                Toggle::make('prices_include_vat')->label($t('prices_include_vat'))->helperText($t('prices_include_vat_help'))
                    ->visible(fn (Get $get) => (bool) $get('vat_payer')),
            ]),
            Section::make($t('section_eu'))
                ->description($t('section_eu_help'))
                ->visible(fn (Get $get) => (bool) $get('vat_payer'))
                ->schema([
                    FeatureNotice::make('tax.reverse_charge_enabled', 'reverse_charge_enabled'),
                    FeatureNotice::make('tax.oss_enabled', 'oss_enabled'),
                    Toggle::make('oss_keep_gross_prices')->label($t('oss_keep_gross'))->helperText($t('oss_keep_gross_help'))
                        ->visible(fn (Get $get) => (bool) $get('oss_enabled')),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('fillEuRates')
                ->label(__('tax::admin.settings.fill_eu_rates'))
                ->icon(Heroicon::OutlinedGlobeEuropeAfrica)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription(__('tax::admin.settings.fill_eu_rates_help'))
                ->action(function (): void {
                    $count = FillEuRates::run();
                    Notification::make()->success()->title(__('tax::admin.settings.fill_eu_rates_done', ['count' => $count]))->send();
                }),
        ];
    }
}
