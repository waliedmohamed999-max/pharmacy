<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>باركود {{ $product->name }}</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; margin: 0; background: #f3f4f6; }
        .sheet { max-width: 520px; margin: 24px auto; background: #fff; border: 1px solid #ddd; border-radius: 12px; padding: 18px; }
        .title { font-size: 20px; font-weight: 700; margin-bottom: 8px; }
        .meta { color: #4b5563; font-size: 13px; margin-bottom: 12px; }
        .barcode { display: flex; justify-content: center; margin: 12px 0; }
        .actions { display: flex; gap: 8px; justify-content: center; margin-top: 12px; }
        .btn { background: #111827; color: #fff; border: 0; padding: 8px 14px; border-radius: 8px; cursor: pointer; text-decoration: none; }
        .btn.light { background: #e5e7eb; color: #111827; }
        @media print {
            body { background: #fff; }
            .sheet { margin: 0 auto; border: none; box-shadow: none; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="title">{{ $product->name }}</div>
        <div class="meta">SKU: {{ $product->sku ?: '-' }} | Barcode: {{ $barcodeValue }}</div>
        <div class="barcode">{!! $barcodeSvg !!}</div>
        <div class="actions">
            <button class="btn" onclick="window.print()">طباعة</button>
            <a class="btn light" href="{{ route('admin.products.edit', $product) }}">الرجوع للمنتج</a>
        </div>
    </div>
</body>
</html>

