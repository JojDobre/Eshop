<?php

namespace Modules\Orders\Filament\Pages;

use App\Filament\Pages\Settings\BaseSettingsPage;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Orders\UnpaidOrderSettings;

/**
 * Nastavenia pripomienky a automatického zrušenia nezaplatených objednávok.
 */
class ManageUnpaidOrderSettings extends BaseSettingsPage
{
    protected static string $settings = UnpaidOrderSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static ?int $navigationSort = 63;

    protected static ?string $slug = 'settings/unpaid-orders';

    public static function getNavigationLabel(): string
    {
        return __('orders::admin.unpaid.nav');
    }

    public function getTitle(): string
    {
        return __('orders::admin.unpaid.title');
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('orders::admin.unpaid.'.$key);

        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('reminder_after_days')->label($t('reminder'))->helperText($t('reminder_help'))
                    ->required()->integer()->minValue(0)->maxValue(60)->suffix($t('days')),
                TextInput::make('cancel_after_days')->label($t('cancel'))->helperText($t('cancel_help'))
                    ->required()->integer()->minValue(0)->maxValue(90)->suffix($t('days'))
                    ->rule(fn (Get $get) => function (string $attribute, mixed $value, \Closure $fail) use ($get, $t): void {
                        $reminder = (int) $get('reminder_after_days');

                        if ((int) $value > 0 && $reminder > 0 && (int) $value <= $reminder) {
                            $fail($t('cancel_after_reminder'));
                        }
                    }),
                Text::make($t('info'))->columnSpanFull(),
            ]),
        ]);
    }
}
