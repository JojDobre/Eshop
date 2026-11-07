<?php

namespace App\Filament\Pages\Settings;

use App\Settings\GeneralSettings;
use App\Settings\MailSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ManageMailSettings extends BaseSettingsPage
{
    protected static string $settings = MailSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?int $navigationSort = 40;

    protected static ?string $slug = 'settings/mail';

    public static function getNavigationLabel(): string
    {
        return __('admin.settings.mail.title');
    }

    public function getTitle(): string
    {
        return __('admin.settings.mail.title');
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('admin.settings.mail.'.$key);
        $isSmtp = fn (Get $get) => $get('mailer') === 'smtp';

        return $schema->components([
            Section::make($t('section_sending'))
                ->description($t('section_sending_help'))
                ->columns(2)
                ->schema([
                    Select::make('mailer')
                        ->label($t('mailer'))
                        ->options([
                            'smtp' => $t('mailer_smtp'),
                            'sendmail' => $t('mailer_sendmail'),
                            'log' => $t('mailer_log'),
                        ])
                        ->required()
                        ->live()
                        ->native(false)
                        ->columnSpanFull(),
                    TextInput::make('host')->label($t('host'))->visible($isSmtp)->required($isSmtp)->maxLength(255),
                    TextInput::make('port')->label($t('port'))->visible($isSmtp)->numeric()->minValue(1)->maxValue(65535),
                    Select::make('encryption')
                        ->label($t('encryption'))
                        ->options(['tls' => 'TLS (STARTTLS)', 'ssl' => 'SSL'])
                        ->placeholder($t('encryption_none'))
                        ->visible($isSmtp)
                        ->native(false),
                    TextInput::make('username')->label($t('username'))->visible($isSmtp)->maxLength(255)->autocomplete('off'),
                    TextInput::make('password')
                        ->label($t('password'))
                        ->password()
                        ->revealable()
                        ->visible($isSmtp)
                        ->maxLength(255)
                        ->autocomplete('new-password'),
                ]),
            Section::make($t('section_sender'))
                ->columns(2)
                ->schema([
                    TextInput::make('from_address')->label($t('from_address'))->email()->helperText($t('from_address_help')),
                    TextInput::make('from_name')->label($t('from_name'))->maxLength(120),
                    TextInput::make('notification_email')
                        ->label($t('notification_email'))
                        ->helperText($t('notification_email_help'))
                        ->email()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTest')
                ->label(__('admin.settings.mail.send_test'))
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('gray')
                ->schema([
                    TextInput::make('email')
                        ->label(__('admin.settings.mail.test_recipient'))
                        ->email()
                        ->required()
                        ->default(fn () => auth()->user()?->email),
                ])
                ->action(function (array $data): void {
                    $settings = app(MailSettings::class);
                    $settings->applyToConfig(app(GeneralSettings::class));

                    try {
                        Mail::raw(__('admin.settings.mail.test_body'), function ($message) use ($data): void {
                            $message->to($data['email'])->subject(__('admin.settings.mail.test_subject'));
                        });
                    } catch (Throwable $e) {
                        Notification::make()->danger()
                            ->title(__('admin.settings.mail.test_failed'))
                            ->body($e->getMessage())
                            ->persistent()
                            ->send();

                        return;
                    }

                    Notification::make()->success()->title(__('admin.settings.mail.test_sent', ['email' => $data['email']]))->send();
                }),
        ];
    }
}
