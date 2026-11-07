<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Odosielanie e-mailov (potvrdenia objednávok, upozornenia pre obchodníka).
 */
class MailSettings extends Settings
{
    /** smtp, sendmail alebo log (e-maily sa iba zapisujú do logu). */
    public string $mailer;

    public ?string $host;

    public ?int $port;

    /** tls, ssl alebo null */
    public ?string $encryption;

    public ?string $username;

    public ?string $password;

    /** Odosielateľ. Ak chýba, použije sa kontaktný e-mail a názov obchodu. */
    public ?string $from_address;

    public ?string $from_name;

    /** Kam chodia upozornenia o nových objednávkach. Ak chýba, kontaktný e-mail obchodu. */
    public ?string $notification_email;

    public static function group(): string
    {
        return 'mail';
    }

    /**
     * Heslo je v databáze uložené šifrovane (aplikačným kľúčom APP_KEY).
     *
     * @return list<string>
     */
    public static function encrypted(): array
    {
        return ['password'];
    }

    public function notificationAddress(GeneralSettings $general): string
    {
        return $this->notification_email ?: $general->contact_email;
    }

    /**
     * Prepíše konfiguráciu Laravelu podľa týchto nastavení.
     */
    public function applyToConfig(GeneralSettings $general): void
    {
        $mailer = in_array($this->mailer, ['smtp', 'sendmail', 'log'], true) ? $this->mailer : 'log';

        config([
            'mail.default' => $mailer,
            'mail.from.address' => $this->from_address ?: $general->contact_email,
            'mail.from.name' => $this->from_name ?: $general->shop_name,
        ]);

        if ($mailer === 'smtp') {
            config([
                'mail.mailers.smtp.host' => $this->host,
                'mail.mailers.smtp.port' => $this->port ?: 587,
                'mail.mailers.smtp.scheme' => $this->encryption === 'ssl' ? 'smtps' : 'smtp',
                'mail.mailers.smtp.username' => $this->username,
                'mail.mailers.smtp.password' => $this->password,
            ]);
        }
    }
}
