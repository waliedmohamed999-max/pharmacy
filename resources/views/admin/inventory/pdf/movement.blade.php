<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>سند حركة مخزون</title>
    <style>
        @page { margin: 120px 28px 50px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; }
        header { position: fixed; top: -100px; left: 0; right: 0; height: 92px; border-bottom: 1px solid #cbd5e1; }
        footer { position: fixed; bottom: -35px; left: 0; right: 0; height: 22px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 10px; }
        .logo { width: 62px; height: 62px; object-fit: contain; }
        .brand td { vertical-align: middle; }
        table.report { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #94a3b8; padding: 6px; text-align: right; }
        th { background: #f1f5f9; }
    </style>
</head>
<body>
<header>
    <table class="brand" width="100%">
        <tr>
            <td width="70">
                @if(!empty($branding['logo_data_uri']))<img src="{{ $branding['logo_data_uri'] }}" class="logo">@endif
            </td>
            <td>
                <div style="font-size:17px;font-weight:700">{{ $branding['company_name'] }}</div>
                <div style="font-size:11px;color:#475569">
                    @if(!empty($branding['contact_phone'])) هاتف: {{ $branding['contact_phone'] }} @endif
                    @if(!empty($branding['contact_email'])) | بريد: {{ $branding['contact_email'] }} @endif
                </div>
            </td>
            <td style="text-align:left;">
                <div style="font-size:16px;font-weight:700">سند حركة مخزون</div>
                <div style="font-size:11px;color:#475569">تاريخ الإصدار: {{ $generatedAt }}</div>
            </td>
        </tr>
    </table>
</header>

<footer>{{ $branding['company_name'] }}</footer>

<main>
    <table class="report">
        <tr><th>رقم الحركة</th><td>{{ $movement->number }}</td><th>النوع</th><td>{{ $movement->type }}</td></tr>
        <tr><th>التاريخ</th><td>{{ optional($movement->movement_date)->format('Y-m-d') }}</td><th>المخزن</th><td>{{ $movement->warehouse?->name }}</td></tr>
        <tr><th>المخزن الهدف</th><td>{{ $movement->targetWarehouse?->name ?: '-' }}</td><th>المنتج</th><td>{{ $movement->product?->name }}</td></tr>
        <tr><th>الكمية</th><td>{{ number_format($movement->qty, 2) }}</td><th>تكلفة الوحدة</th><td>{{ number_format($movement->unit_cost, 4) }}</td></tr>
        <tr><th>القيمة</th><td>{{ number_format($movement->line_total, 2) }}</td><th>المرجع</th><td>{{ $movement->reference_type ?: '-' }}</td></tr>
        <tr><th>ملاحظات</th><td colspan="3">{{ $movement->notes ?: '-' }}</td></tr>
    </table>
</main>
</body>
</html>
