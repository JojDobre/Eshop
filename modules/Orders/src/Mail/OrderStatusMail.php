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
 * Informácia pre zákazníka o zmene stavu objednávky (odoslaná, pripravená na vyzdvihnutie…).
 */
class OrderStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Order $order) {}

    public static function templateKey(Order $order): string
    {
        return 'orders.status.'.$order->status->value;
    }

    /**
     * @return array{subject: string, body: string}
     */
    private function rendered(): array
    {
        return app(EmailTemplates::class)->render(self::templateKey($this->order), OrderMailVariables::for($this->order), $this->order->locale, OrderMailVariables::RAW);
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
