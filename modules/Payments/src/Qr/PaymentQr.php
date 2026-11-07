<?php

namespace Modules\Payments\Qr;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Throwable;

/**
 * QR kód na platbu prevodom. Formát sa vyberie podľa meny a účtu:
 * CZK → QR Platba, EUR na slovenský účet → PAY by square, ostatné EUR → EPC QR (SEPA).
 */
class PaymentQr
{
    public function payload(PaymentQrData $data): ?string
    {
        $iban = $data->normalizedIban();

        if ($iban === '' || $data->amount <= 0) {
            return null;
        }

        return match (true) {
            $data->currency === 'CZK' => SpdQr::encode($data),
            $data->currency === 'EUR' && str_starts_with($iban, 'SK') => PayBySquare::encode($data),
            $data->currency === 'EUR' => EpcQr::encode($data),
            default => null,
        };
    }

    /**
     * Obrázok ako data URI: PNG (spoľahlivé v PDF), ak chýba GD, tak SVG.
     */
    public function dataUri(PaymentQrData $data): ?string
    {
        $payload = $this->payload($data);

        if ($payload === null) {
            return null;
        }

        try {
            $png = extension_loaded('gd');

            return (new QRCode(new QROptions([
                'outputInterface' => $png ? QRGdImagePNG::class : QRMarkupSVG::class,
                'eccLevel' => EccLevel::M,
                'scale' => 6,
                'quietzoneSize' => 2,
                'outputBase64' => true,
            ])))->render($payload);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}
