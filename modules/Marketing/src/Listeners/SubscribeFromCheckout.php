<?php

namespace Modules\Marketing\Listeners;

use Modules\Marketing\MarketingSettings;
use Modules\Marketing\Newsletter\Newsletter;
use Modules\Orders\Events\OrderPlaced;

/**
 * Zaškrtnutý súhlas s newsletterom v pokladni: prihlásenie s potvrdením e-mailom.
 */
class SubscribeFromCheckout
{
    public function __construct(private readonly Newsletter $newsletter) {}

    public function handle(OrderPlaced $event): void
    {
        if (! Newsletter::enabled() || ! MarketingSettings::get('newsletter_checkout') || ! request()->boolean('newsletter')) {
            return;
        }

        $this->newsletter->subscribe($event->order->email, $event->order->billing_name, 'checkout', request()->ip());
    }
}
