<?php

namespace Modules\Packeta\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Packeta\PacketaSettings;
use Modules\Shipping\Filament\Pages\CarrierSettingsPage;

class ManagePacketaSettings extends CarrierSettingsPage
{
    protected static string $settings = PacketaSettings::class;

    protected static ?int $navigationSort = 41;

    protected static ?string $slug = 'settings/packeta';

    protected static function carrierKey(): string
    {
        return 'packeta';
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('packeta::admin.'.$key);

        return $schema->components([
            Section::make($t('section_keys'))->description($t('section_keys_help'))->columns(2)->schema([
                TextInput::make('api_key')->label($t('api_key'))->helperText($t('api_key_help'))->maxLength(32)->regex('/^[A-Za-z0-9]{16}$/'),
                TextInput::make('api_password')->label($t('api_password'))->password()->revealable()->autocomplete('off')
                    ->maxLength(64)->regex('/^[A-Za-z0-9]{32}$/'),
                TextInput::make('sender')->label($t('sender'))->helperText($t('sender_help'))->maxLength(64),
                Select::make('label_format')->label($t('label_format'))->selectablePlaceholder(false)
                    ->options(['A6 on A6' => 'A6 (termotlačiareň)', 'A6 on A4' => 'A6 na A4 (4 na stranu)', 'A7 on A7' => 'A7', 'A7 on A4' => 'A7 na A4 (8 na stranu)']),
            ]),
        ]);
    }
}
