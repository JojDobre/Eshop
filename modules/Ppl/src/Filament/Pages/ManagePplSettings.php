<?php

namespace Modules\Ppl\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Modules\Ppl\PplSettings;
use Modules\Shipping\Filament\Pages\CarrierSettingsPage;
use Modules\Shipping\Filament\Resources\ShippingMethods\ShippingMethodResource;

class ManagePplSettings extends CarrierSettingsPage
{
    protected static string $settings = PplSettings::class;

    protected static ?int $navigationSort = 46;

    protected static ?string $slug = 'settings/ppl';

    protected static function carrierKey(): string
    {
        return 'ppl';
    }

    protected static function hasPickupPointList(): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key, array $replace = []) => __('ppl::admin.'.$key, $replace);

        return $schema->components([
            Section::make($t('section_keys'))->description($t('section_keys_help'))->columns(2)->schema([
                TextInput::make('client_id')->label($t('client_id'))->maxLength(100),
                TextInput::make('client_secret')->label($t('client_secret'))->password()->revealable()->autocomplete('off')->maxLength(200),
            ]),
            Section::make($t('section_shipments'))->columns(2)->schema([
                Toggle::make('business')->label($t('business'))->helperText($t('business_help')),
                Select::make('label_page')->label($t('label_page'))->selectablePlaceholder(false)
                    ->options(['A4' => $t('label_a4'), 'Default' => $t('label_single')]),
                TextInput::make('cod_iban')->label($t('cod_iban'))->helperText($t('cod_iban_help'))->maxLength(34),
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
