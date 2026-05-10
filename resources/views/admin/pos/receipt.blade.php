<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إيصال {{ $sale->number }}</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; margin: 0; background: #f3f4f6; }
        .wrap { max-width: 360px; margin: 20px auto; background: #fff; border: 1px solid #ddd; padding: 14px; }
        h1 { font-size: 16px; margin: 0 0 6px; text-align: center; }
        .muted { color: #666; font-size: 12px; }
        .row { display: flex; justify-content: space-between; gap: 8px; font-size: 13px; margin: 4px 0; }
        .line { border-top: 1px dashed #999; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { padding: 6px 2px; border-bottom: 1px dashed #ddd; text-align: right; }
        .total { font-weight: 700; font-size: 14px; }
        .center { text-align: center; }
        .actions { text-align: center; margin: 10px 0 0; }
        @media print {
            body { background: #fff; }
            .wrap { margin: 0 auto; border: none; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>صيدلية د. محمد رمضان</h1>
        <div class="center muted">إيصال نقاط بيع</div>
        <div class="line"></div>

        <div class="row"><span>رقم العملية</span><span>{{ $sale->number }}</span></div>
        <div class="row"><span>التاريخ</span><span>{{ $sale->created_at?->format('Y-m-d H:i') }}</span></div>
        <div class="row"><span>المخزن</span><span>{{ $sale->warehouse?->name }}</span></div>
        <div class="row"><span>العميل</span><span>{{ $sale->customer_name ?: ($sale->contact?->name ?? 'عميل نقدي') }}</span></div>
        <div class="line"></div>

        <table>
            <thead>
                <tr>
                    <th>الصنف</th>
                    <th>ك</th>
                    <th>سعر</th>
                    <th>إجمالي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td>{{ number_format((float) $item->qty, 2) }}</td>
                        <td>{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td>{{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="line"></div>
        <div class="row"><span>SubTotal</span><span>{{ number_format((float) $sale->subtotal, 2) }} ج.م</span></div>
        <div class="row"><span>خصم</span><span>{{ number_format((float) $sale->discount, 2) }} ج.م</span></div>
        <div class="row"><span>ضريبة</span><span>{{ number_format((float) $sale->tax, 2) }} ج.م</span></div>
        <div class="row total"><span>الإجمالي</span><span>{{ number_format((float) $sale->total, 2) }} ج.م</span></div>
        <div class="row"><span>المدفوع</span><span>{{ number_format((float) $sale->paid_amount, 2) }} ج.م</span></div>
        <div class="row"><span>الباقي/المرتجع</span><span>{{ number_format((float) $sale->change_amount, 2) }} ج.م</span></div>

        <div class="line"></div>
        <div class="center muted">شكرًا لتسوقكم معنا</div>
        <div class="actions">
            <button onclick="window.print()">طباعة</button>
        </div>
    </div>
</body>
</html>

