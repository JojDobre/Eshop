<?php

namespace App\Filament\Pages\Settings;

use App\Core\Support\Currencies;
use App\Core\Support\EcbRates;
use App\Settings\CurrencySettings;
use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Throwable;

class ManageCurrencySettings extends BaseSettingsPage
{
    protected static string $settings = CurrencySettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyEuro;

    protected static ?int $navigationSort = 12;

    protected static ?string $slug = 'settings/currencies';

    public static function getNavigationLabel(): string
    {
        return __('admin.currencies.title');
    }

    public function getTitle(): string
    {
        return __('admin.currencies.title');
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key, array $replace = []) => __('admin.currencies.'.$key, $replace);
        $default = app(GeneralSettings::class)->default_currency;
        $others = collect(config('eshop.currencies'))->except($default);

        $rows = $others->map(fn (string $symbol, string $code) => Grid::make(3)
            ->visible(fn (Get $get) => in_array($code, $get('enabled') ?? [], true))
            ->schema([
                TextInput::make("rates.{$code}")
                    ->label($t('rate', ['currency' => $code, 'default' => $default]))
                    ->helperText($t('rate_help', ['default' => $default, 'currency' => $code]))
                    ->numeric()->minValue(0.000001)
                    ->required(fn (Get $get) => in_array($code, $get('enabled') ?? [], true))
                    ->columnSpan(2),
                Select::make("rounding.{$code}")
                    ->label($t('rounding'))
                    ->options([
                        Currencies::ROUND_MATH => $t('rounding_math'),
                        Currencies::ROUND_WHOLE => $t('rounding_whole'),
                        Currencies::ROUND_NINETY_NINE => $t('rounding_ninety_nine'),
                    ])
                    ->default(Currencies::ROUND_MATH)
                    ->native(false),
            ]))->values()->all();

        return $schema->components([
            Section::make()
                ->description($t('intro', ['default' => $default]))
                ->schema([
                    CheckboxList::make('enabled')
                        ->label($t('enabled'))
                        ->options($others->mapWithKeys(fn (string $symbol, string $code) => [$code => "{$code} ({$symbol})"])->all())
                        ->columns(4)
                        ->live(),
                    Toggle::make('auto_update')
                        ->label($t('auto_update'))
                        ->helperText(fn () => $t('auto_update_help').(app(CurrencySettings::class)->rates_updated_at
                            ? ' '.$t('updated_at', ['time' => Carbon::parse(app(CurrencySettings::class)->rates_updated_at)->diffForHumans()])
                            : '')),
                ]),
            Section::make($t('rates'))
                ->visible(fn (Get $get) => ($get('enabled') ?? []) !== [])
                ->schema($rows),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('updateRates')
                ->label(__('admin.currencies.update_now'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->action(function (EcbRates $ecb): void {
                    try {
                        $ecb->update();
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title(__('admin.currencies.update_failed'))->body($e->getMessage())->persistent()->send();

                        return;
                    }

                    $this->fillForm();
                    Notification::make()->success()->title(__('admin.currencies.updated'))->send();
                }),
        ];
    }

    protected function afterSave(): void
    {
        app(Currencies::class)->refresh();
    }
}
