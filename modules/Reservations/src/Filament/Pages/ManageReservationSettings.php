<?php

namespace Modules\Reservations\Filament\Pages;

use App\Filament\Forms\MoneyInput;
use App\Filament\Pages\Settings\BaseSettingsPage;
use App\Filament\Pages\Settings\ManageFeatures;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Modules\Reservations\ReservationSettings;

class ManageReservationSettings extends BaseSettingsPage
{
    protected static string $settings = ReservationSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?int $navigationSort = 62;

    protected static ?string $slug = 'settings/reservations';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('reservations.manage') ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('reservations::admin.plural');
    }

    public function getTitle(): string
    {
        return __('reservations::admin.settings_title');
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('reservations::admin.settings.'.$key);
        $hours = __('reservations::admin.settings.hours');

        return $schema->components([
            Section::make($t('services'))->columns(2)->schema([
                TextInput::make('horizon_days')->label($t('horizon_days'))->required()->integer()->minValue(1)->maxValue(365)->suffix(__('reservations::admin.settings.days')),
                TextInput::make('lead_hours')->label($t('lead_hours'))->helperText($t('lead_hours_help'))->required()->integer()->minValue(0)->maxValue(720)->suffix($hours),
                TextInput::make('cancel_hours')->label($t('cancel_hours'))->helperText($t('cancel_hours_help'))->required()->integer()->minValue(0)->maxValue(720)->suffix($hours),
                TextInput::make('reminder_hours')->label($t('reminder_hours'))->helperText($t('reminder_hours_help'))->required()->integer()->minValue(0)->maxValue(168)->suffix($hours),
            ]),
            Section::make($t('products'))->description($t('products_help'))->columns(2)->schema([
                TextInput::make('product_hold_days')->label($t('product_hold_days'))->required()->integer()->minValue(1)->maxValue(60)->suffix(__('reservations::admin.settings.days')),
                MoneyInput::make('product_deposit')->label($t('product_deposit'))->helperText($t('product_deposit_help'))->required(),
            ]),
            Section::make($t('ical'))->description($t('ical_help'))->schema([
                Text::make(fn () => route('reservations.feed', app(ReservationSettings::class)->ical_token))->copyable(),
            ])->headerActions([
                Action::make('regenerate')->label($t('ical_regenerate'))->requiresConfirmation()->color('gray')
                    ->action(function (): void {
                        $settings = app(ReservationSettings::class);
                        $settings->ical_token = Str::random(40);
                        $settings->save();
                    }),
            ]),
        ]);
    }

    /** V menu pod položkou „ManageFeatures“ (zobrazí sa po jej otvorení). */
    public static function getNavigationParentItem(): ?string
    {
        return ManageFeatures::getNavigationLabel();
    }
}
