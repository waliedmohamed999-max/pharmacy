<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>محضر جرد</title>
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
                <div style="font-size:16px;font-weight:700">محضر جرد مخزني</div>
                <div style="font-size:11px;color:#475569">تاريخ الإصدار: {{ $generatedAt }}</div>
            </td>
        </tr>
    </table>
</header>

<footer>{{ $branding['company_name'] }}</footer>

<main>
    <table class="report">
        <tr><th>رقم الجلسة</th><td>{{ $count->number }}</td><th>المخزن</th><td>{{ $count->warehouse?->name }}</td></tr>
        <tr><th>تاريخ الجرد</th><td>{{ optional($count->count_date)->format('Y-m-d') }}</td><th>الحالة</th><td>{{ $count->status === 'posted' ? 'معتمد' : 'مسودة' }}</td></tr>
        <tr><th>ملاحظات</th><td colspan="3">{{ $count->notes ?: '-' }}</td></tr>
    </table>

    <table class="report">
        <thead>
        <tr><th>المنتج</th><th>الدفتري</th><th>الفعلي</th><th>الفرق</th><th>التكلفة</th><th>قيمة الفرق</th></tr>
        </thead>
        <tbody>
        @foreach($count->items as $item)
            <tr>
                <td>{{ $item->product?->name }}</td>
                <td>{{ number_format($item->snapshot_qty, 2) }}</td>
                <td>{{ number_format((float)($item->counted_qty ?? 0), 2) }}</td>
                <td>{{ number_format($item->diff_qty, 2) }}</td>
                <td>{{ number_format($item->unit_cost_snapshot, 4) }}</td>
                <td>{{ number_format($item->diff_value, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</main>
</body>
</html>
