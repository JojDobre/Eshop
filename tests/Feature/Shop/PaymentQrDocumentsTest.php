<?php

namespace Tests\Feature\Shop;

use App\Settings\CompanySettings;
use chillerlan\QRCode\QRCode;
use Illuminate\Support\Facades\Mail;
use Modules\Invoices\InvoicePdf;
use Modules\Invoices\PaymentQrRegistry;
use Modules\Orders\Mail\OrderConfirmation;
use Modules\Orders\Models\Order;
use Modules\Payments\Qr\PaymentQr;

/**
 * QR kód na platbu sa naozaj dá prečítať: obrázok zo stránky objednávky a z dokladu sa dekóduje
 * a porovná s platobnými údajmi. E-mail s potvrdením obsahuje údaje na platbu a PDF doklad sa vytvorí.
 */
class PaymentQrDocumentsTest extends ShopTestCase
{
    private function orderWithBankTransfer(): Order
    {
        $company = app(CompanySettings::class);
        $company->company_name = 'Farma Krepelka';
        $company->iban = 'SK31 1200 0000 1987 4263 7541';
        $company->swift = 'TATRSKBX';
        $company->save();

        $product = $this->product('Násadové vajcia', '1.20', 100);
        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 10]);
        $this->post(route('checkout.store'), $this->checkoutData());

        return Order::query()->latest('id')->firstOrFail();
    }

    /** Prečíta prvý QR kód (data URI PNG) v HTML. */
    private function decodeQr(string $html): string
    {
        $this->assertMatchesRegularExpression('#data:image/png;base64,([A-Za-z0-9+/=]+)#', $html);
        preg_match('#data:image/png;base64,([A-Za-z0-9+/=]+)#', $html, $match);

        return (string) (new QRCode)->readFromBlob(base64_decode($match[1]));
    }

    public function test_order_page_qr_decodes_to_the_payment_data(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('Dekódovanie QR potrebuje rozšírenie GD.');
        }

        Mail::fake();
        $order = $this->orderWithBankTransfer();
        $expected = app(PaymentQr::class)->payload($order->paymentQrData());

        $html = $this->get($order->publicUrl())->assertOk()->getContent();

        $this->assertNotNull($expected);
        $this->assertSame($expected, $this->decodeQr($html));
    }

    public function test_invoice_pdf_has_a_readable_qr_and_email_has_bank_details(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('Dekódovanie QR potrebuje rozšírenie GD.');
        }

        Mail::fake();
        $order = $this->orderWithBankTransfer();
        $proforma = $order->invoices()->firstOrFail();

        // QR v doklade: rovnaký obrázok, aký ide do PDF.
        $qr = app(PaymentQrRegistry::class)->dataUri($proforma);
        $this->assertNotNull($qr);
        $decoded = $this->decodeQr((string) $qr);
        $this->assertNotSame('', $decoded);

        $pdf = app(InvoicePdf::class)->render($proforma);
        $this->assertStringStartsWith('%PDF', $pdf);

        // Potvrdenie objednávky: účet, variabilný symbol a suma.
        $html = (new OrderConfirmation($order))->render();
        $this->assertStringContainsString('SK31 1200 0000 1987 4263 7541', $html);
        $this->assertStringContainsString($order->variableSymbol(), $html);
    }
}
