<?php

namespace Modules\Balikobot\Filament\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Modules\Balikobot\BalikobotSettings;
use Modules\Shipping\Filament\Pages\CarrierSettingsPage;

class ManageBalikobotSettings extends CarrierSettingsPage
{
    protected static string $settings = BalikobotSettings::class;

    protected static ?int $navigationSort = 48;

    protected static ?string $slug = 'settings/balikobot';

    protected static function carrierKey(): string
    {
        return 'balikobot';
    }

    protected static function hasPickupPointList(): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key, array $replace = []) => __('balikobot::admin.'.$key, $replace);

        return $schema->components([
            Section::make($t('section_keys'))->description($t('section_keys_help'))->columns(2)->schema([
                TextInput::make('api_user')->label($t('api_user'))->maxLength(100),
                TextInput::make('api_key')->label($t('api_key'))->password()->revealable()->autocomplete('off')->maxLength(200),
            ]),
            Section::make($t('section_shipments'))->schema([
                Toggle::make('auto_order')->label($t('auto_order'))->helperText($t('auto_order_help')),
                Text::make($t('how_to')),
                Text::make(fn () => $t('pickup_points', ['count' => static::pickupPointCount()])),
            ]),
        ]);
    }

    /** V menu pod položkou „ShippingMethodResource“ (zobrazí sa po jej otvorení). */
    public static function getNavigationParentItem(): ?string
    {
        return \Modules\Shipping\Filament\Resources\ShippingMethods\ShippingMethodResource::getNavigationLabel();
    }
}
