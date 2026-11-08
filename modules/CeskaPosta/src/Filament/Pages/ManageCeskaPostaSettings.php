<?php

namespace Modules\CeskaPosta\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Modules\CeskaPosta\CeskaPostaSettings;
use Modules\Shipping\Filament\Pages\CarrierSettingsPage;

class ManageCeskaPostaSettings extends CarrierSettingsPage
{
    protected static string $settings = CeskaPostaSettings::class;

    protected static ?int $navigationSort = 44;

    protected static ?string $slug = 'settings/ceskaposta';

    protected static function carrierKey(): string
    {
        return 'ceskaposta';
    }

    protected static function hasPickupPointList(): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key, array $replace = []) => __('ceskaposta::admin.'.$key, $replace);

        return $schema->components([
            Section::make($t('section_keys'))->description($t('section_keys_help'))->columns(2)->schema([
                TextInput::make('api_token')->label($t('api_token'))->password()->revealable()->autocomplete('off')->maxLength(100),
                TextInput::make('secret_key')->label($t('secret_key'))->password()->revealable()->autocomplete('off')->maxLength(200),
                Toggle::make('test_mode')->label($t('test_mode'))->helperText($t('test_mode_help'))->columnSpanFull(),
            ]),
            Section::make($t('section_contract'))->columns(2)->schema([
                TextInput::make('customer_id')->label($t('customer_id'))->maxLength(6),
                TextInput::make('post_code')->label($t('post_code'))->maxLength(6)->helperText($t('post_code_help')),
                TextInput::make('location_number')->label($t('location_number'))->integer()->minValue(1),
                TextInput::make('contract_number')->label($t('contract_number'))->maxLength(20)->helperText($t('contract_number_help')),
            ]),
            Section::make($t('section_shipments'))->columns(2)->schema([
                Select::make('label_form')->label($t('label_form'))->selectablePlaceholder(false)
                    ->options(collect([101, 100, 102, 103, 20, 200, 201, 202])->mapWithKeys(fn ($id) => [$id => $t('forms.'.$id)])->all()),
                Select::make('parcel_size')->label($t('parcel_size'))->helperText($t('parcel_size_help'))
                    ->options(['S' => 'S', 'M' => 'M', 'L' => 'L', 'XL' => 'XL'])->placeholder($t('parcel_size_none')),
                Text::make(fn () => $t('pickup_points', ['count' => static::pickupPointCount()]))->columnSpanFull(),
            ]),
        ]);
    }
}
