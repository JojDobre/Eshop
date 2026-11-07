<?php

namespace Modules\Catalog\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;

class LowStockNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Product $product, public readonly ?ProductVariant $variant = null) {}

    private function label(): string
    {
        return $this->variant ? $this->product->name.' ('.$this->variant->name().')' : $this->product->name;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('catalog::admin.low_stock.mail_subject', ['product' => $this->label()]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'catalog::mail.low-stock', with: [
            'productName' => $this->label(),
            'count' => $this->variant?->stock_quantity ?? $this->product->stock_quantity,
            'adminUrl' => url('/admin/products/'.$this->product->id.'/edit'),
        ]);
    }
}
