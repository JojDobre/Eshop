<?php

namespace Modules\Orders\Filament\Pages;

use App\Filament\Forms\FeatureNotice;
use App\Filament\Pages\Settings\BaseSettingsPage;
use App\Filament\Pages\Settings\ManageFeatures;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Orders\GiftVoucherSettings;

class ManageGiftVoucherSettings extends BaseSettingsPage
{
    protected static string $settings = GiftVoucherSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static ?int $navigationSort = 62;

    protected static ?string $slug = 'settings/gift-vouchers';

    public static function getNavigationLabel(): string
    {
        return __('orders::admin.vouchers.plural');
    }

    public function getTitle(): string
    {
        return __('orders::admin.vouchers.settings_title');
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('orders::admin.vouchers.'.$key);

        return $schema->components([
            Section::make()->columns(2)->schema([
                FeatureNotice::make('orders.gift_vouchers', 'enabled'),
                TextInput::make('validity_months')->label($t('validity_months'))->helperText($t('validity_months_help'))
                    ->required()->integer()->minValue(0)->maxValue(120)->suffix($t('months')),
                Text::make($t('how_to'))->columnSpanFull(),
            ]),
        ]);
    }

    /** V menu pod položkou „ManageFeatures“ (zobrazí sa po jej otvorení). */
    public static function getNavigationParentItem(): ?string
    {
        return ManageFeatures::getNavigationLabel();
    }
}
