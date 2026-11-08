<?php

namespace Modules\Marketing\Newsletter;

use App\Core\Content\ContentLocale;
use App\Core\Mail\TemplateMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Marketing\MarketingSettings;
use Modules\Marketing\Models\Subscriber;
use Throwable;

/**
 * Prihlásenie s potvrdením e-mailom, potvrdenie, odhlásenie a synchronizácia s externou službou.
 */
class Newsletter
{
    public function __construct(private readonly MarketingSettings $settings) {}

    public static function enabled(): bool
    {
        return (bool) MarketingSettings::get('newsletter_enabled');
    }

    /**
     * Prihlási e-mail (pošle potvrdenie). Už prihláseného nemení.
     */
    public function subscribe(string $email, ?string $name, string $source, ?string $ip = null): Subscriber
    {
        $subscriber = Subscriber::query()->firstOrNew(['email' => Str::lower(trim($email))]);

        if ($subscriber->exists && $subscriber->status === Subscriber::SUBSCRIBED) {
            return $subscriber;
        }

        $subscriber->fill([
            'name' => $name ?: $subscriber->name,
            'locale' => app(ContentLocale::class)->current(),
            'status' => Subscriber::PENDING,
            'source' => $source,
            'consent_text' => __('marketing::shop.newsletter_consent'),
            'ip_address' => $ip,
            'unsubscribed_at' => null,
        ]);
        $subscriber->token = Str::random(48);
        $subscriber->save();

        try {
            Mail::to($subscriber->email, $subscriber->name)->send(new TemplateMail('marketing.newsletter_confirm', [
                'confirm_url' => $subscriber->confirmUrl(),
            ], $subscriber->locale, ['confirm_url']));
        } catch (Throwable $e) {
            report($e);
        }

        return $subscriber;
    }

    public function confirm(Subscriber $subscriber): void
    {
        if ($subscriber->status !== Subscriber::SUBSCRIBED) {
            $subscriber->update(['status' => Subscriber::SUBSCRIBED, 'confirmed_at' => now(), 'unsubscribed_at' => null]);
            $this->sync($subscriber);
        }
    }

    public function unsubscribe(Subscriber $subscriber): void
    {
        if ($subscriber->status !== Subscriber::UNSUBSCRIBED) {
            $wasSubscribed = $subscriber->status === Subscriber::SUBSCRIBED;
            $subscriber->update(['status' => Subscriber::UNSUBSCRIBED, 'unsubscribed_at' => now()]);

            if ($wasSubscribed) {
                $this->sync($subscriber);
            }
        }
    }

    public function provider(): ?NewsletterProvider
    {
        return match ($this->settings->newsletter_provider) {
            'mailchimp' => filled($this->settings->mailchimp_api_key) && filled($this->settings->mailchimp_list_id)
                ? new MailchimpProvider((string) $this->settings->mailchimp_api_key, (string) $this->settings->mailchimp_list_id) : null,
            'ecomail' => filled($this->settings->ecomail_api_key) && filled($this->settings->ecomail_list_id)
                ? new EcomailProvider((string) $this->settings->ecomail_api_key, (string) $this->settings->ecomail_list_id) : null,
            default => null,
        };
    }

    /**
     * Pošle zmenu do externej služby (chyba sa zapíše do logu, odber v obchode platí ďalej).
     */
    public function sync(Subscriber $subscriber, bool $throw = false): bool
    {
        $provider = $this->provider();

        if ($provider === null) {
            return false;
        }

        try {
            $provider->sync($subscriber);
            $subscriber->forceFill(['synced_at' => now()])->save();

            return true;
        } catch (Throwable $e) {
            if ($throw) {
                throw $e;
            }

            report($e);

            return false;
        }
    }
}
