<?php

namespace Modules\Invoices\Mail;

use App\Core\Mail\EmailTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Invoices\InvoicePdf;
use Modules\Invoices\Models\Invoice;

/**
 * Doklad v prílohe e-mailu (faktúra po zaplatení, dobropis, ručné odoslanie z administrácie).
 */
class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Invoice $invoice) {}

    public static function templateKey(Invoice $invoice): string
    {
        return 'invoices.'.$invoice->type->value;
    }

    /**
     * @return array{subject: string, body: string}
     */
    private function rendered(): array
    {
        $orderUrl = $this->invoice->order?->publicUrl();

        return app(EmailTemplates::class)->render(self::templateKey($this->invoice), [
            'customer_name' => (string) ($this->invoice->customer['name'] ?? ''),
            'document_number' => $this->invoice->number,
            'order_number' => (string) ($this->invoice->customer['order_number'] ?? ''),
            'order_url' => $orderUrl ?? url('/'),
        ], $this->invoice->locale, ['order_url']);
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
        return [
            Attachment::fromData(fn () => app(InvoicePdf::class)->render($this->invoice), $this->invoice->fileName())->withMime('application/pdf'),
        ];
    }
}
