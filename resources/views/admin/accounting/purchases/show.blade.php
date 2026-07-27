@extends('admin.layouts.app')

@section('page-title', 'فاتورة مشتريات ضريبية')
@section('page-subtitle', $invoice->number . ' · QR زاتكا جاهز للقراءة')

@section('page-actions')
    @if(!$printMode)
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.accounting.purchases.index') }}" class="btn-secondary">كل الفواتير</a>
            <a href="{{ route('admin.accounting.purchases.print', $invoice) }}" target="_blank" class="btn-primary">طباعة الفاتورة</a>
        </div>
    @endif
@endsection

@section('content')
@php
    $currency = 'ر.س';
    $format = fn ($value) => number_format((float) $value, 2);
@endphp

@if($printMode)
    <style>
        body { background: #fff !important; }
        .admin-sidebar, .admin-topbar, .admin-page-heading, #sidebarBackdrop { display: none !important; }
        .admin-page-main { margin: 0 !important; width: 100% !important; }
        .admin-page-inner { max-width: none !important; padding: 0 !important; }
        @media print {
            .no-print { display: none !important; }
            .invoice-sheet { box-shadow: none !important; border: 0 !important; }
        }
    </style>
    <script>window.addEventListener('load', () => window.print());</script>
@endif

<article class="invoice-sheet mx-auto max-w-5xl overflow-hidden rounded-[1.6rem] border border-slate-200 bg-white shadow-2xl shadow-slate-900/8">
    <header class="bg-gradient-to-l from-slate-950 via-emerald-800 to-teal-600 p-6 text-white">
        <div class="flex flex-wrap items-start justify-between gap-5">
            <div>
                <div class="text-xs font-black uppercase text-emerald-100">Tax Purchase Invoice</div>
                <h1 class="mt-2 text-3xl font-black">فاتورة مشتريات ضريبية</h1>
                <div class="mt-3 flex flex-wrap gap-2 text-xs font-bold text-white/75">
                    <span class="rounded-full bg-white/12 px-3 py-1">مرتبطة بصيغة QR زاتكا الأساسية</span>
                    <span class="rounded-full bg-white/12 px-3 py-1">مرحلة الربط API: {{ $invoice->zatca_status === 'ready' ? 'جاهزة لاحقاً' : $invoice->zatca_status }}</span>
                </div>
            </div>
            <div class="text-left">
                <div class="text-sm font-bold text-white/70">رقم الفاتورة</div>
                <div class="mt-1 text-3xl font-black">{{ $invoice->number }}</div>
                @if($invoice->supplier_invoice_number)
                    <div class="mt-2 text-xs font-bold text-white/75">رقم المورد: {{ $invoice->supplier_invoice_number }}</div>
                @endif
            </div>
        </div>
    </header>

    <section class="grid gap-4 border-b border-slate-100 p-6 lg:grid-cols-[1fr_240px]">
        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                <div class="text-xs font-black text-slate-500">المورد</div>
                <div class="mt-2 text-lg font-black text-slate-950">{{ $invoice->contact?->name ?: '-' }}</div>
                <div class="mt-2 text-sm font-bold text-slate-500">{{ $invoice->contact?->address ?: 'لا يوجد عنوان مسجل' }}</div>
                <div class="mt-3 grid gap-1 text-sm font-bold text-slate-600">
                    <div>الرقم الضريبي: <span class="text-slate-950">{{ $invoice->supplier_tax_number ?: '-' }}</span></div>
                    <div>الهاتف: <span class="text-slate-950">{{ $invoice->contact?->phone ?: '-' }}</span></div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                <div class="text-xs font-black text-slate-500">بيانات الاستلام</div>
                <div class="mt-2 grid gap-2 text-sm font-bold text-slate-600">
                    <div class="flex justify-between gap-3"><span>المخزن</span><span class="text-slate-950">{{ $invoice->warehouse?->name ?: '-' }}</span></div>
                    <div class="flex justify-between gap-3"><span>تاريخ الفاتورة</span><span class="text-slate-950">{{ optional($invoice->invoice_date)->format('Y-m-d') }}</span></div>
                    <div class="flex justify-between gap-3"><span>تاريخ الاستحقاق</span><span class="text-slate-950">{{ optional($invoice->due_date)->format('Y-m-d') ?: '-' }}</span></div>
                    <div class="flex justify-between gap-3"><span>الحالة</span><span class="text-emerald-700">{{ $invoice->status }}</span></div>
                </div>
            </div>
        </div>

        <div class="grid place-items-center rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-center">
            <div class="rounded-xl bg-white p-2 shadow-sm">
                {!! $zatcaQrSvg !!}
            </div>
            <div class="mt-2 text-xs font-black text-emerald-800">QR زاتكا TLV</div>
        </div>
    </section>

    <section class="p-6">
        <div class="overflow-hidden rounded-2xl border border-slate-200">
            <table class="w-full text-sm">
                <thead class="bg-slate-950 text-white">
                    <tr>
                        <th class="px-4 py-3 text-right">#</th>
                        <th class="px-4 py-3 text-right">الصنف</th>
                        <th class="px-4 py-3 text-right">الوصف</th>
                        <th class="px-4 py-3 text-right">الكمية</th>
                        <th class="px-4 py-3 text-right">تكلفة الوحدة</th>
                        <th class="px-4 py-3 text-right">الإجمالي</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($invoice->items as $item)
                        <tr>
                            <td class="px-4 py-3 font-black text-slate-500">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 font-bold text-slate-800">{{ $item->product?->name ?: '-' }}</td>
                            <td class="px-4 py-3 font-bold text-slate-700">{{ $item->description }}</td>
                            <td class="px-4 py-3 font-bold">{{ $format($item->qty) }}</td>
                            <td class="px-4 py-3 font-bold">{{ $format($item->unit_cost) }} {{ $currency }}</td>
                            <td class="px-4 py-3 font-black">{{ $format($item->line_total) }} {{ $currency }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="grid gap-4 border-t border-slate-100 p-6 lg:grid-cols-[1fr_360px]">
        <div>
            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                <div class="text-xs font-black text-slate-500">ملاحظات</div>
                <p class="mt-2 min-h-16 text-sm font-bold leading-7 text-slate-700">{{ $invoice->notes ?: 'لا توجد ملاحظات.' }}</p>
            </div>
            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-100 bg-white p-3">
                <div class="text-xs font-black text-slate-500">باركود رقم الفاتورة</div>
                <div class="mt-2 overflow-x-auto">{!! $barcodeSvg !!}</div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="space-y-3 text-sm font-bold">
                <div class="flex justify-between gap-3"><span class="text-slate-500">الإجمالي قبل الخصم</span><span>{{ $format($invoice->subtotal) }} {{ $currency }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-slate-500">الخصم</span><span>{{ $format($invoice->discount) }} {{ $currency }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-slate-500">الوعاء الضريبي</span><span>{{ $format($invoice->taxable_amount) }} {{ $currency }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-slate-500">ضريبة القيمة المضافة {{ $format($invoice->tax_rate) }}%</span><span>{{ $format($invoice->tax) }} {{ $currency }}</span></div>
                <div class="border-t border-slate-100 pt-3">
                    <div class="flex items-end justify-between gap-3">
                        <span class="text-base font-black text-slate-900">الإجمالي شامل الضريبة</span>
                        <span class="text-2xl font-black text-emerald-700">{{ $format($invoice->total) }} {{ $currency }}</span>
                    </div>
                </div>
                <div class="flex justify-between gap-3"><span class="text-slate-500">المسدد</span><span>{{ $format($invoice->paid_amount) }} {{ $currency }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-slate-500">المتبقي</span><span>{{ $format($invoice->balance) }} {{ $currency }}</span></div>
            </div>
        </div>
    </section>

    <footer class="border-t border-slate-100 bg-slate-50 p-5 text-center text-xs font-bold leading-6 text-slate-500">
        هذه الفاتورة تحفظ QR بصيغة TLV الأساسية المتوافقة مع متطلبات قراءة الفاتورة الضريبية. الربط الإلكتروني المباشر مع زاتكا يحتاج شهادة وبيئة تكامل رسمية.
    </footer>
</article>
@endsection
