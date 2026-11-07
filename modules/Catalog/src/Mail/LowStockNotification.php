<?php

namespace Modules\Catalog\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Catalog\Models\Product;

class LowStockNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Product $product) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('catalog::admin.low_stock.mail_subject', ['product' => $this->product->name]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'catalog::mail.low-stock', with: [
            'product' => $this->product,
            'adminUrl' => url('/admin/products/'.$this->product->id.'/edit'),
        ]);
    }
}
