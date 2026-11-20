<?php

namespace Modules\Orders\Mail;

use App\Core\Mail\EmailTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Orders\Models\Order;

/**
 * Pripomienka platby nezaplatenej objednávky (text sa dá upraviť v administrácii).
 */
class PaymentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Order $order, public readonly ?string $cancelDate = null) {}

    /**
     * @return array{subject: string, body: string}
     */
    private function rendered(): array
    {
        $variables = [...OrderMailVariables::for($this->order), 'cancel_notice' => $this->cancelDate
            ? __('orders::mail.cancel_notice', ['date' => $this->cancelDate], $this->order->locale)
            : ''];

        return app(EmailTemplates::class)->render('orders.payment_reminder', $variables, $this->order->locale, OrderMailVariables::RAW);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->rendered()['subject']);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.template', with: ['body' => $this->rendered()['body']]);
    }
}
