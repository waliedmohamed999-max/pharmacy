<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ملصقات الباركود</title>
    <style>
        @page { size: A4; margin: 10mm; }
        body { margin: 0; font-family: Tahoma, Arial, sans-serif; background: #fff; }
        .actions { padding: 12px; text-align: center; }
        .btn { border: 0; background: #111827; color: #fff; padding: 8px 14px; border-radius: 8px; cursor: pointer; }
        .sheet { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; padding: 8px; }
        .label {
            border: 1px dashed #9ca3af;
            border-radius: 8px;
            padding: 6px;
            min-height: 145px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }
        .name { font-size: 12px; font-weight: 700; line-height: 1.2; height: 30px; overflow: hidden; }
        .meta { font-size: 10px; color: #4b5563; }
        .price { font-size: 12px; font-weight: 700; margin-top: 3px; }
        .barcode { display: flex; justify-content: center; transform: scale(0.9); transform-origin: top center; }
        @media print {
            .actions { display: none; }
            .sheet { padding: 0; }
            .label { break-inside: avoid; page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button class="btn" onclick="window.print()">طباعة الملصقات</button>
    </div>

    <div class="sheet">
        @foreach($labels as $label)
            <div class="label">
                <div>
                    <div class="name">{{ $label['name'] }}</div>
                    <div class="meta">SKU: {{ $label['sku'] ?: '-' }}</div>
                    <div class="price">{{ number_format((float) $label['price'], 2) }} ج.م</div>
                </div>
                <div class="barcode">{!! $label['svg'] !!}</div>
            </div>
        @endforeach
    </div>
</body>
</html>

