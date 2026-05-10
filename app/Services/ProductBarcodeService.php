<?php

namespace App\Services;

use App\Models\Product;

class ProductBarcodeService
{
    public function assignIfMissing(Product $product, ?string $preferred = null): string
    {
        $preferred = $this->normalize($preferred ?? '');
        $current = $this->normalize((string) ($product->barcode ?? ''));

        if ($current !== '') {
            return $current;
        }

        $base = $preferred !== '' ? $preferred : $this->normalize((string) ($product->sku ?? ''));
        if ($base === '') {
            $base = 'PRD' . $product->id;
        }

        $barcode = $this->uniqueBarcode($base, $product->id);
        $product->barcode = $barcode;
        $product->save();

        return $barcode;
    }

    public function normalize(string $value): string
    {
        $value = strtoupper(trim($value));
        // Code39 allowed set: A-Z, 0-9, space, - . $ / + %
        $value = preg_replace('/[^A-Z0-9\-\.\$\/\+\% ]/', '', $value) ?? '';
        return trim($value);
    }

    public function svg(string $code, int $height = 70): string
    {
        $patterns = $this->patterns();
        $code = $this->normalize($code);
        if ($code === '') {
            return '';
        }

        $full = '*' . $code . '*';
        $narrow = 2;
        $wide = 5;
        $gap = 1;

        $x = 10;
        $bars = [];
        $chars = str_split($full);
        foreach ($chars as $char) {
            if (!isset($patterns[$char])) {
                continue;
            }

            $seq = str_split($patterns[$char]); // 9 items, odd=bar even=space
            foreach ($seq as $idx => $widthType) {
                $w = $widthType === 'w' ? $wide : $narrow;
                $isBar = $idx % 2 === 0;
                if ($isBar) {
                    $bars[] = '<rect x="' . $x . '" y="8" width="' . $w . '" height="' . $height . '" fill="#111827" />';
                }
                $x += $w;
            }
            $x += $gap;
        }

        $width = $x + 10;
        $textY = $height + 24;
        $safe = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');

        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . ($height + 34) . '" viewBox="0 0 ' . $width . ' ' . ($height + 34) . '">' .
            implode('', $bars) .
            '<text x="' . (int) ($width / 2) . '" y="' . $textY . '" text-anchor="middle" font-size="14" font-family="Arial, sans-serif" fill="#111827">' . $safe . '</text>' .
            '</svg>';
    }

    private function uniqueBarcode(string $base, int $productId): string
    {
        $base = $this->normalize($base);
        if ($base === '') {
            $base = 'PRD' . $productId;
        }

        $candidate = $base;
        $i = 1;
        while (
            Product::query()
                ->where('barcode', $candidate)
                ->where('id', '!=', $productId)
                ->exists()
        ) {
            $candidate = $base . '-' . $i;
            $i++;
        }

        return $candidate;
    }

    private function patterns(): array
    {
        return [
            '0' => 'nnnwwnwnn',
            '1' => 'wnnwnnnnw',
            '2' => 'nnwwnnnnw',
            '3' => 'wnwwnnnnn',
            '4' => 'nnnwwnnnw',
            '5' => 'wnnwwnnnn',
            '6' => 'nnwwwnnnn',
            '7' => 'nnnwnnwnw',
            '8' => 'wnnwnnwnn',
            '9' => 'nnwwnnwnn',
            'A' => 'wnnnnwnnw',
            'B' => 'nnwnnwnnw',
            'C' => 'wnwnnwnnn',
            'D' => 'nnnnwwnnw',
            'E' => 'wnnnwwnnn',
            'F' => 'nnwnwwnnn',
            'G' => 'nnnnnwwnw',
            'H' => 'wnnnnwwnn',
            'I' => 'nnwnnwwnn',
            'J' => 'nnnnwwwnn',
            'K' => 'wnnnnnnww',
            'L' => 'nnwnnnnww',
            'M' => 'wnwnnnnwn',
            'N' => 'nnnnwnnww',
            'O' => 'wnnnwnnwn',
            'P' => 'nnwnwnnwn',
            'Q' => 'nnnnnnwww',
            'R' => 'wnnnnnwwn',
            'S' => 'nnwnnnwwn',
            'T' => 'nnnnwnwwn',
            'U' => 'wwnnnnnnw',
            'V' => 'nwwnnnnnw',
            'W' => 'wwwnnnnnn',
            'X' => 'nwnnwnnnw',
            'Y' => 'wwnnwnnnn',
            'Z' => 'nwwnwnnnn',
            '-' => 'nwnnnnwnw',
            '.' => 'wwnnnnwnn',
            ' ' => 'nwwnnnwnn',
            '$' => 'nwnwnwnnn',
            '/' => 'nwnwnnnwn',
            '+' => 'nwnnnwnwn',
            '%' => 'nnnwnwnwn',
            '*' => 'nwnnwnwnn',
        ];
    }
}

