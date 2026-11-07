<?php

namespace Modules\Orders\Mail;

use App\Core\Mail\EmailTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Orders\Models\DownloadLink;
use Modules\Orders\Models\Order;

/**
 * Odkazy na stiahnutie digitálnych produktov (text sa dá upraviť v administrácii).
 */
class DownloadsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Order $order) {}

    /**
     * Zoznam odkazov v Markdowne.
     */
    public static function linksList(Order $order): string
    {
        return DownloadLink::query()->where('order_id', $order->id)->orderBy('id')->get()
            ->map(fn (DownloadLink $link) => '- ['.str_replace(['[', ']'], '', $link->file_name).']('.$link->url().')')
            ->implode("\n");
    }

    /**
     * @return array{subject: string, body: string}
     */
    private function rendered(): array
    {
        $variables = [...OrderMailVariables::for($this->order), 'download_links' => self::linksList($this->order)];

        return app(EmailTemplates::class)->render('orders.downloads', $variables, $this->order->locale, [...OrderMailVariables::RAW, 'download_links']);
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
