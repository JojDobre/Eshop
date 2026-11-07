<?php

namespace Modules\Orders\Mail;

use App\Core\Mail\EmailTemplates;
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

    /**
     * @return array{subject: string, body: string}
     */
    private function rendered(): array
    {
        return app(EmailTemplates::class)->render('orders.notification', OrderMailVariables::for($this->order), null, OrderMailVariables::RAW);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->rendered()['subject'],
            replyTo: [new Address($this->order->email, $this->order->billing_name)],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.template', with: ['body' => $this->rendered()['body']]);
    }
}
