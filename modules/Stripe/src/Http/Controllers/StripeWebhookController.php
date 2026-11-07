<?php

namespace Modules\Stripe\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Stripe\Actions\ConfirmStripeSession;
use Modules\Stripe\StripeSettings;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

/**
 * Upozornenia zo Stripe (overené podpisom). Spracúva dokončené platby.
 */
class StripeWebhookController extends Controller
{
    private const EVENTS = ['checkout.session.completed', 'checkout.session.async_payment_succeeded'];

    public function __invoke(Request $request, StripeSettings $settings, ConfirmStripeSession $confirm): Response
    {
        if (blank($settings->webhook_secret)) {
            return response('Webhook secret is not configured.', 404);
        }

        try {
            $event = Webhook::constructEvent($request->getContent(), (string) $request->header('Stripe-Signature'), (string) $settings->webhook_secret);
        } catch (SignatureVerificationException|UnexpectedValueException) {
            return response('Invalid signature.', 400);
        }

        if (in_array($event->type, self::EVENTS, true)) {
            $confirm->handle($event->data->object->toArray());
        }

        return response('OK');
    }
}
