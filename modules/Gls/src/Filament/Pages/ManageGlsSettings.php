<?php

namespace Modules\Gls\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Modules\Gls\GlsApi;
use Modules\Gls\GlsSettings;
use Modules\Shipping\Filament\Pages\CarrierSettingsPage;
use Modules\Shipping\Filament\Resources\ShippingMethods\ShippingMethodResource;

class ManageGlsSettings extends CarrierSettingsPage
{
    protected static string $settings = GlsSettings::class;

    protected static ?int $navigationSort = 42;

    protected static ?string $slug = 'settings/gls';

    protected static function carrierKey(): string
    {
        return 'gls';
    }

    protected static function hasPickupPointList(): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key, array $replace = []) => __('gls::admin.'.$key, $replace);

        return $schema->components([
            Section::make($t('section_keys'))->description($t('section_keys_help'))->columns(2)->schema([
                Select::make('country')->label($t('account_country'))->selectablePlaceholder(false)
                    ->options(collect(GlsApi::COUNTRIES)->mapWithKeys(fn ($code) => [$code => strtoupper($code)])->all()),
                TextInput::make('client_number')->label($t('client_number'))->integer(),
                TextInput::make('username')->label($t('username'))->email()->maxLength(120),
                TextInput::make('password')->label($t('password'))->password()->revealable()->autocomplete('off')->maxLength(120),
                Toggle::make('test_mode')->label($t('test_mode'))->helperText($t('test_mode_help')),
            ]),
            Section::make($t('section_shipments'))->columns(2)->schema([
                Select::make('printer')->label($t('printer'))->selectablePlaceholder(false)
                    ->options(['A4_2x2' => 'A4 (4 na stranu)', 'A4_4x1' => 'A4 (4 pod sebou)', 'Thermo' => $t('thermo'), 'Connect' => 'Connect']),
                Toggle::make('notify_recipient')->label($t('notify_recipient'))->helperText($t('notify_recipient_help')),
                Text::make(fn () => $t('pickup_points', ['count' => static::pickupPointCount()]))->columnSpanFull(),
            ]),
        ]);
    }

    /** V menu pod položkou „ShippingMethodResource“ (zobrazí sa po jej otvorení). */
    public static function getNavigationParentItem(): ?string
    {
        return ShippingMethodResource::getNavigationLabel();
    }
}
