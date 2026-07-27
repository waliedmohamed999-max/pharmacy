<?php

namespace App\Services;

use App\Models\PurchaseInvoice;
use Carbon\CarbonInterface;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;

class ZatcaQrService
{
    public function purchaseInvoicePayload(PurchaseInvoice $invoice): string
    {
        $sellerName = (string) ($invoice->contact?->name ?? config('app.name', 'Pharmacy ERP'));
        $taxNumber = (string) ($invoice->supplier_tax_number ?: $invoice->contact?->tax_number ?: '');
        $timestamp = $this->timestamp($invoice->invoice_date);

        return $this->tlvBase64([
            1 => $sellerName,
            2 => $taxNumber,
            3 => $timestamp,
            4 => number_format((float) $invoice->total, 2, '.', ''),
            5 => number_format((float) $invoice->tax, 2, '.', ''),
        ]);
    }

    public function tlvBase64(array $fields): string
    {
        $binary = '';

        foreach ($fields as $tag => $value) {
            $value = (string) $value;
            $bytes = $value;
            $length = strlen($bytes);
            $binary .= chr((int) $tag) . chr($length) . $bytes;
        }

        return base64_encode($binary);
    }

    public function svg(string $payload, int $size = 180): string
    {
        if (trim($payload) === '') {
            return '';
        }

        $qrCode = new QrCode(
            data: $payload,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: 8,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(15, 23, 42),
            backgroundColor: new Color(255, 255, 255)
        );

        return (new SvgWriter())->write($qrCode)->getString();
    }

    private function timestamp($date): string
    {
        if ($date instanceof CarbonInterface) {
            return $date->copy()->startOfDay()->toIso8601String();
        }

        return now()->parse($date ?: now())->startOfDay()->toIso8601String();
    }
}
