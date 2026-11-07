<?php

namespace Modules\Orders\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Orders\Models\Order;

/**
 * Upozornenie pre obchodníka na novú objednávku.
 */
class NewOrderNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('orders::mail.notification.subject', ['number' => $this->order->number]),
            replyTo: [new Address($this->order->email, $this->order->billing_name)],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'orders::mail.notification', with: [
            'order' => $this->order->loadMissing('items'),
            'adminUrl' => url('/admin/orders/'.$this->order->id),
        ]);
    }
}
