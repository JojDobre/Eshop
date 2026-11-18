<?php

namespace App\Filament\Pages\Settings;

use App\Core\Banking\BankAccount;
use App\Settings\CompanySettings;
use BackedEnum;
use Closure;
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
                    // Účet sa overí (kontrolné číslice IBAN); domáci tvar číslo/kód banky sa uloží ako IBAN.
                    TextInput::make('iban')->label($t('iban'))->helperText($t('iban_help'))->maxLength(42)
                        ->rule(fn () => function (string $attribute, mixed $value, Closure $fail) use ($t): void {
                            if (filled($value) && BankAccount::iban((string) $value, $this->countryForAccount()) === '') {
                                $fail($t('iban_invalid'));
                            }
                        })
                        ->dehydrateStateUsing(fn (?string $state) => filled($state)
                            ? (BankAccount::iban($state, $this->countryForAccount()) ?: $state) : null),
                    TextInput::make('swift')->label($t('swift'))->helperText($t('swift_help'))->maxLength(11)
                        ->rule('regex:/^\s*[A-Za-z]{6}[A-Za-z0-9]{2}([A-Za-z0-9]{3})?\s*$/')
                        ->dehydrateStateUsing(fn (?string $state) => filled($state) ? strtoupper(trim($state)) : null),
                ]),
        ]);
    }

    /** Krajina pre prevod domáceho čísla účtu na IBAN (podľa sídla, inak Česko). */
    private function countryForAccount(): string
    {
        $country = strtoupper((string) ($this->data['country'] ?? ''));

        return in_array($country, ['CZ', 'SK'], true) ? $country : 'CZ';
    }

    /** V menu pod položkou „ManageGeneralSettings“ (zobrazí sa po jej otvorení). */
    public static function getNavigationParentItem(): ?string
    {
        return ManageGeneralSettings::getNavigationLabel();
    }
}
