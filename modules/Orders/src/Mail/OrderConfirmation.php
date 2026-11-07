<?php

namespace Modules\Orders\Mail;

use App\Core\Mail\EmailTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderMailAttachments;

/**
 * Potvrdenie objednávky pre zákazníka (text sa dá upraviť v administrácii).
 */
class OrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Order $order) {}

    /**
     * @return array{subject: string, body: string}
     */
    private function rendered(): array
    {
        return app(EmailTemplates::class)->render('orders.confirmation', OrderMailVariables::for($this->order), $this->order->locale, OrderMailVariables::RAW);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->rendered()['subject']);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.template', with: ['body' => $this->rendered()['body']]);
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return app(OrderMailAttachments::class)->for(OrderMailAttachments::CONFIRMATION, $this->order);
    }
}
