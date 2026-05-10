<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }}</title>
    <style>
        @page { margin: 125px 28px 55px 28px; }
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; }
        header { position: fixed; top: -105px; left: 0; right: 0; height: 95px; border-bottom: 1px solid #cbd5e1; }
        footer { position: fixed; bottom: -35px; left: 0; right: 0; height: 24px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 10px; }
        .page:before { content: counter(page); }
        .brand { width: 100%; border-collapse: collapse; }
        .brand td { vertical-align: middle; }
        .logo { width: 70px; height: 70px; object-fit: contain; }
        .company { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
        .meta { color: #475569; font-size: 11px; }
        .title { font-size: 16px; font-weight: 700; text-align: left; }
        .subtitle { color: #475569; font-size: 11px; text-align: left; }
        .footer-right { float: right; }
        .footer-left { float: left; }
        table.report { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.report th, table.report td { border: 1px solid #94a3b8; padding: 6px; text-align: right; }
        table.report th { background: #f1f5f9; font-weight: 700; }
    </style>
</head>
<body>
<header>
    <table class="brand">
        <tr>
            <td style="width: 78px;">
                @if(!empty($branding['logo_data_uri']))
                    <img src="{{ $branding['logo_data_uri'] }}" class="logo" alt="logo">
                @endif
            </td>
            <td>
                <div class="company">{{ $branding['company_name'] }}</div>
                <div class="meta">
                    @if(!empty($branding['contact_phone'])) هاتف: {{ $branding['contact_phone'] }} @endif
                    @if(!empty($branding['contact_email'])) | بريد: {{ $branding['contact_email'] }} @endif
                </div>
            </td>
            <td style="text-align:left;">
                <div class="title">{{ $reportTitle }}</div>
                <div class="subtitle">من: {{ $filters['date_from'] ?: '-' }} | إلى: {{ $filters['date_to'] ?: '-' }}</div>
                <div class="subtitle">تاريخ الإصدار: {{ $generatedAt }}</div>
            </td>
        </tr>
    </table>
</header>

<footer>
    <span class="footer-right">{{ $branding['company_name'] }}</span>
    <span class="footer-left">صفحة <span class="page"></span></span>
</footer>

<main>
    <table class="report">
        <thead><tr><th colspan="3">الإيرادات</th></tr><tr><th>الكود</th><th>الحساب</th><th>القيمة</th></tr></thead>
        <tbody>
        @foreach($revenues as $r)
            <tr><td>{{ $r->code }}</td><td>{{ $r->name }}</td><td>{{ number_format($r->amount, 2) }}</td></tr>
        @endforeach
        <tr><th colspan="2">إجمالي الإيرادات</th><th>{{ number_format($totalRevenue, 2) }}</th></tr>
        </tbody>
    </table>

    <table class="report">
        <thead><tr><th colspan="3">المصروفات</th></tr><tr><th>الكود</th><th>الحساب</th><th>القيمة</th></tr></thead>
        <tbody>
        @foreach($expenses as $r)
            <tr><td>{{ $r->code }}</td><td>{{ $r->name }}</td><td>{{ number_format($r->amount, 2) }}</td></tr>
        @endforeach
        <tr><th colspan="2">إجمالي المصروفات</th><th>{{ number_format($totalExpense, 2) }}</th></tr>
        <tr><th colspan="2">صافي الربح</th><th>{{ number_format($netProfit, 2) }}</th></tr>
        </tbody>
    </table>
</main>
</body>
</html>
