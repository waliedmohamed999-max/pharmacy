<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'لوحة إدارة الصيدلية')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="page-shell admin-shell">
@if(View::hasSection('admin_full_bleed'))
    @yield('content')
@else
<div class="admin-main-grid min-h-screen">
    <main class="admin-page-main min-w-0">
        @include('admin.partials.topbar')

        <div class="admin-page-inner">
        <div class="admin-page-heading">
            <div class="min-w-0">
                <div class="mb-1 text-xs font-black uppercase tracking-wide text-emerald-600">Pharmacy ERP</div>
                <h1 class="page-title">@yield('page-title', 'لوحة التحكم')</h1>
                <p class="page-subtitle">@yield('page-subtitle', 'نظام تشغيل صيدلي لإدارة الطلبات والمخزون والمالية والواجهة الخارجية')</p>
            </div>
            <div class="shrink-0">@yield('page-actions')</div>
        </div>

        @if(View::hasSection('breadcrumbs'))
            <nav class="card-premium px-4 py-2 text-sm text-slate-500 mb-4">@yield('breadcrumbs')</nav>
        @endif

        @include('admin.partials.flash')

        <section class="admin-content-surface">
            @yield('content')
        </section>
        </div>
    </main>

    <div>
        @include('admin.partials.sidebar')
    </div>
</div>
@endif

<div id="confirmModal" class="fixed inset-0 z-[80] hidden">
    <div class="absolute inset-0 bg-slate-900/45" data-confirm-cancel></div>
    <div class="relative max-w-md mx-auto mt-24 card-premium p-5">
        <h3 class="text-lg font-black mb-2">تأكيد الإجراء</h3>
        <p class="text-sm text-slate-600 mb-4">هل أنت متأكد من تنفيذ هذا الإجراء؟ لا يمكن التراجع بسهولة.</p>
        <div class="flex gap-2 justify-end">
            <button type="button" class="btn-secondary" data-confirm-cancel>إلغاء</button>
            <button type="button" class="btn-danger" id="confirmModalOk">تأكيد الحذف</button>
        </div>
    </div>
</div>
</body>
</html>
