<?php

namespace Tests\Unit\Payments;

use Modules\Payments\Qr\EpcQr;
use Modules\Payments\Qr\LzmaLiteralEncoder;
use Modules\Payments\Qr\PayBySquare;
use Modules\Payments\Qr\PaymentQr;
use Modules\Payments\Qr\PaymentQrData;
use Modules\Payments\Qr\SpdQr;
use PHPUnit\Framework\TestCase;

class PaymentQrTest extends TestCase
{
    private function data(string $iban = 'SK31 1200 0000 1987 4263 7541', string $currency = 'EUR', int $amount = 1290): PaymentQrData
    {
        return new PaymentQrData($iban, 'TATRSKBX', $amount, $currency, '2026001', 'Kaviareň u Jožka', 'Objednávka 2026001');
    }

    public function test_lzma_output_matches_liblzma(): void
    {
        // Referenčný výstup z liblzma (Python: lzma.compress, FORMAT_RAW, LZMA1 lc=3 lp=0 pb=2).
        $this->assertSame('0083fffbffffc0000000', bin2hex(LzmaLiteralEncoder::compress('')));
        $this->assertSame(
            '00281b08277770879205eb30a8adea442df12622600c64421b3d2ecf53ffbffff4de4000',
            bin2hex(LzmaLiteralEncoder::compress(hex2bin('506c61746261203230323620c5bd6c74c3bd206bc3b4c588'))),
        );
    }

    public function test_pay_by_square_structure(): void
    {
        $code = PayBySquare::encode($this->data());
        $this->assertMatchesRegularExpression('/^[0-9A-V]+$/', $code);

        $bits = implode('', array_map(fn (string $c) => str_pad(decbin(strpos('0123456789ABCDEFGHIJKLMNOPQRSTUV', $c)), 5, '0', STR_PAD_LEFT), str_split($code)));
        $bytes = implode('', array_map(fn (string $b) => chr(bindec($b)), str_split(substr($bits, 0, intdiv(strlen($bits), 8) * 8), 8)));

        $payload = implode("\t", ['', '1', '1', '12.90', 'EUR', '', '2026001', '', '', '', 'Objednávka 2026001', '1', 'SK3112000000198742637541', 'TATRSKBX', '0', '0', 'Kaviareň u Jožka', '', '']);
        $this->assertSame("\x00\x00", substr($bytes, 0, 2));
        $this->assertSame(strlen($payload) + 4, unpack('v', substr($bytes, 2, 2))[1]);
        $this->assertStringStartsWith(bin2hex(LzmaLiteralEncoder::compress(pack('V', crc32($payload)).$payload)), bin2hex(substr($bytes, 4)));
    }

    public function test_czech_qr_payment(): void
    {
        $this->assertSame(
            'SPD*1.0*ACC:CZ6508000000192000145399+TATRSKBX*AM:480.50*CC:CZK*X-VS:2026001*RN:Kaviaren u Jozka*MSG:Objednavka 2026001',
            SpdQr::encode($this->data('CZ65 0800 0000 1920 0014 5399', 'CZK', 48050)),
        );
    }

    public function test_epc_qr(): void
    {
        $this->assertSame(
            "BCD\n002\n1\nSCT\nTATRSKBX\nKaviareň u Jožka\nDE89370400440532013000\nEUR12.90\n\n\nVS2026001 Objednávka 2026001",
            EpcQr::encode($this->data('DE89 3704 0044 0532 0130 00')),
        );
    }

    public function test_format_is_chosen_by_currency_and_account(): void
    {
        $qr = new PaymentQr;

        $this->assertStringStartsWith('SPD*', (string) $qr->payload($this->data('CZ6508000000192000145399', 'CZK')));
        $this->assertMatchesRegularExpression('/^[0-9A-V]+$/', (string) $qr->payload($this->data()));
        $this->assertStringStartsWith('BCD', (string) $qr->payload($this->data('DE89370400440532013000')));
        $this->assertNull($qr->payload($this->data(currency: 'USD')));
        $this->assertNull($qr->payload($this->data(amount: 0)));
        $this->assertStringStartsWith('data:image/', (string) $qr->dataUri($this->data()));
    }
}
