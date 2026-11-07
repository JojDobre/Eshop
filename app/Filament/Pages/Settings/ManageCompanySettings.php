<?php

namespace App\Filament\Pages\Settings;

use App\Settings\CompanySettings;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageCompanySettings extends BaseSettingsPage
{
    protected static string $settings = CompanySettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'settings/company';

    public static function getNavigationLabel(): string
    {
        return __('admin.settings.company.title');
    }

    public function getTitle(): string
    {
        return __('admin.settings.company.title');
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('admin.settings.company.'.$key);

        return $schema->components([
            Section::make($t('section_company'))
                ->columns(2)
                ->schema([
                    TextInput::make('company_name')->label($t('company_name'))->maxLength(200)->columnSpanFull(),
                    TextInput::make('company_id')->label($t('company_id'))->maxLength(20),
                    TextInput::make('tax_id')->label($t('tax_id'))->maxLength(20),
                    TextInput::make('vat_id')->label($t('vat_id'))->maxLength(20),
                    Textarea::make('registration')->label($t('registration'))->helperText($t('registration_help'))->rows(2)->maxLength(500)->columnSpanFull(),
                ]),
            Section::make($t('section_address'))
                ->columns(2)
                ->schema([
                    TextInput::make('street')->label($t('street'))->maxLength(200)->columnSpanFull(),
                    TextInput::make('zip')->label($t('zip'))->maxLength(20),
                    TextInput::make('city')->label($t('city'))->maxLength(120),
                    Select::make('country')
                        ->label($t('country'))
                        ->options(collect(config('eshop.countries'))->mapWithKeys(fn ($code) => [$code => __('countries.'.$code)]))
                        ->native(false),
                ]),
            Section::make($t('section_bank'))
                ->columns(2)
                ->schema([
                    TextInput::make('iban')->label($t('iban'))->maxLength(42),
                    TextInput::make('swift')->label($t('swift'))->maxLength(11),
                ]),
        ]);
    }
}
