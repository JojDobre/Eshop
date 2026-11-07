<?php

namespace Modules\Orders\Mail;

use App\Settings\CompanySettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Orders\Models\Order;

/**
 * Potvrdenie objednávky pre zákazníka (v jazyku, v ktorom objednával).
 */
class OrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('orders::mail.confirmation.subject', ['number' => $this->order->number]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'orders::mail.confirmation', with: [
            'order' => $this->order->loadMissing('items'),
            'company' => app(CompanySettings::class),
        ]);
    }
}
