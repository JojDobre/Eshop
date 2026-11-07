<?php

namespace App\Core\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail z upraviteľnej šablóny (pre jednoduché e-maily modulov bez vlastnej triedy).
 */
class TemplateMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, string>  $variables
     * @param  list<string>  $rawVariables
     */
    public function __construct(
        public readonly string $templateKey,
        public readonly array $variables,
        public readonly ?string $templateLocale = null,
        public readonly array $rawVariables = [],
    ) {}

    /**
     * @return array{subject: string, body: string}
     */
    private function rendered(): array
    {
        return app(EmailTemplates::class)->render($this->templateKey, $this->variables, $this->templateLocale, $this->rawVariables);
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
