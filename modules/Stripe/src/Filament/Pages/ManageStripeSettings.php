<?php

namespace Modules\Stripe\Filament\Pages;

use App\Filament\Pages\Settings\BaseSettingsPage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Payments\Filament\Resources\PaymentMethods\PaymentMethodResource;
use Modules\Stripe\StripeApi;
use Modules\Stripe\StripeSettings;
use Throwable;

class ManageStripeSettings extends BaseSettingsPage
{
    protected static string $settings = StripeSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?int $navigationSort = 31;

    protected static ?string $slug = 'settings/stripe';

    public static function getNavigationLabel(): string
    {
        return __('stripe::admin.title');
    }

    public function getTitle(): string
    {
        return __('stripe::admin.title');
    }

    public static function canAccess(): bool
    {
        return (bool) (auth()->user()?->can('payments.manage') || auth()->user()?->can('settings.manage'));
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key, array $replace = []) => __('stripe::admin.'.$key, $replace);

        return $schema->components([
            Section::make($t('section_keys'))
                ->description($t('section_keys_help'))
                ->schema([
                    Toggle::make('enabled')->label($t('enabled'))->helperText($t('enabled_help')),
                    TextInput::make('publishable_key')->label($t('publishable_key'))->placeholder('pk_live_…')
                        ->regex('/^pk_(test|live)_[A-Za-z0-9]+$/')->maxLength(255),
                    TextInput::make('secret_key')->label($t('secret_key'))->placeholder('sk_live_…')->helperText($t('secret_key_help'))
                        ->password()->revealable()->autocomplete('off')
                        ->regex('/^(sk|rk)_(test|live)_[A-Za-z0-9]+$/')->maxLength(255)
                        ->requiredIf('enabled', true),
                ]),
            Section::make($t('section_webhook'))
                ->schema([
                    Text::make($t('webhook_help', ['url' => route('stripe.webhook')])),
                    Text::make('checkout.session.completed, checkout.session.async_payment_succeeded')->fontFamily('mono'),
                    TextInput::make('webhook_secret')->label($t('webhook_secret'))->placeholder('whsec_…')
                        ->password()->revealable()->autocomplete('off')
                        ->regex('/^whsec_[A-Za-z0-9]+$/')->maxLength(255),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('verify')
                ->label(__('stripe::admin.verify'))
                ->icon(Heroicon::OutlinedSignal)
                ->color('gray')
                ->action(function (StripeApi $api): void {
                    if (blank(app(StripeSettings::class)->secret_key)) {
                        Notification::make()->warning()->title(__('stripe::admin.verify_missing'))->send();

                        return;
                    }

                    try {
                        $mode = $api->verify();
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title(__('stripe::admin.verify_failed'))->body($e->getMessage())->persistent()->send();

                        return;
                    }

                    Notification::make()->success()->title(__('stripe::admin.verify_ok', ['mode' => __('stripe::admin.mode_'.$mode)]))->send();
                }),
        ];
    }

    /** V menu pod položkou „PaymentMethodResource“ (zobrazí sa po jej otvorení). */
    public static function getNavigationParentItem(): ?string
    {
        return PaymentMethodResource::getNavigationLabel();
    }
}
