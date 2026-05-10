@extends('admin.layouts.app')

@section('page-title', 'الطلبات')
@section('page-subtitle', 'فصل طلبات المتجر الإلكتروني عن الطلبات اليدوية داخل الصيدلية')

@section('page-actions')
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.pos.index') }}" class="btn-primary">طلب يدوي جديد</a>
        <a href="{{ route('store.home') }}" target="_blank" rel="noreferrer" class="btn-secondary">عرض المتجر</a>
    </div>
@endsection

@section('content')
@php
    $statusLabels = [
        'new' => 'جديد',
        'preparing' => 'قيد التجهيز',
        'shipped' => 'تم الشحن',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغي',
    ];

    $summaryCards = [
        ['label' => 'طلبات المتجر', 'value' => $stats['store_count'], 'hint' => 'قادمة من واجهة العميل', 'tone' => 'from-emerald-600 to-teal-500'],
        ['label' => 'طلبات يدوية', 'value' => $stats['manual_count'], 'hint' => 'مسجلة من نقطة البيع', 'tone' => 'from-sky-600 to-cyan-500'],
        ['label' => 'طلبات متجر نشطة', 'value' => $stats['store_pending'], 'hint' => 'تحتاج متابعة وتجهيز', 'tone' => 'from-amber-500 to-orange-500'],
        ['label' => 'يدوي اليوم', 'value' => $stats['manual_today'], 'hint' => 'عمليات صيدلية مباشرة', 'tone' => 'from-violet-600 to-fuchsia-500'],
    ];
@endphp

<div class="space-y-5">
    <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        @foreach($summaryCards as $card)
            <div class="card-premium overflow-hidden p-0">
                <div class="relative p-4">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-l {{ $card['tone'] }}"></div>
                    <p class="text-xs font-black text-slate-400">{{ $card['label'] }}</p>
                    <div class="mt-3 flex items-end justify-between gap-3">
                        <div>
                            <p class="text-3xl font-black text-slate-950">{{ number_format($card['value']) }}</p>
                            <p class="mt-1 text-xs font-bold text-slate-500">{{ $card['hint'] }}</p>
                        </div>
                        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-emerald-50 text-emerald-700">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>
                            </svg>
                        </span>
                    </div>
                </div>
            </div>
        @endforeach
    </section>

    <section class="grid gap-5 2xl:grid-cols-2">
        <div class="card-premium p-4">
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700 ring-1 ring-emerald-100">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Online Store
                    </div>
                    <h2 class="mt-3 text-xl font-black text-slate-950">طلبات من المتجر</h2>
                    <p class="mt-1 text-sm font-semibold text-slate-500">طلبات العملاء القادمة من واجهة المتجر الإلكتروني وتحتاج متابعة التسليم.</p>
                </div>
                <a href="{{ route('store.home') }}" target="_blank" rel="noreferrer" class="btn-secondary">فتح المتجر</a>
            </div>

            <div class="table-wrap">
                <table class="table-premium">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>العميل</th>
                        <th>الحالة</th>
                        <th>الإجمالي</th>
                        <th>التاريخ</th>
                        <th>إجراء</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($storeOrders as $order)
                        <tr>
                            <td class="font-black">#{{ $order->id }}</td>
                            <td>
                                <div class="font-black text-slate-950">{{ $order->customer_name }}</div>
                                <div class="mt-1 text-xs font-semibold text-slate-400">{{ $order->phone }} · {{ $order->items_count }} عنصر</div>
                            </td>
                            <td><span class="badge-status-{{ $order->status }}">{{ $statusLabels[$order->status] ?? $order->status }}</span></td>
                            <td class="font-black">{{ number_format($order->total, 2) }} ج.م</td>
                            <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="btn-secondary">عرض</a>
                                    <a href="{{ route('admin.finance.index', ['invoice_id' => $order->id]) }}" class="btn-secondary">المالية</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state">لا توجد طلبات متجر حالياً.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $storeOrders->appends(request()->except('store_page'))->links() }}</div>
        </div>

        <div class="card-premium p-4">
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1 text-xs font-black text-sky-700 ring-1 ring-sky-100">
                        <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                        Pharmacy POS
                    </div>
                    <h2 class="mt-3 text-xl font-black text-slate-950">طلبات يدوية من الصيدلية</h2>
                    <p class="mt-1 text-sm font-semibold text-slate-500">طلبات وفواتير تم إنشاؤها من نقطة البيع أو داخل الصيدلية مباشرة.</p>
                </div>
                <a href="{{ route('admin.pos.index') }}" class="btn-primary">إنشاء طلب يدوي</a>
            </div>

            <div class="table-wrap">
                <table class="table-premium">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>العميل</th>
                        <th>POS</th>
                        <th>الحالة</th>
                        <th>الإجمالي</th>
                        <th>إجراء</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($manualOrders as $order)
                        <tr>
                            <td class="font-black">#{{ $order->id }}</td>
                            <td>
                                <div class="font-black text-slate-950">{{ $order->customer_name }}</div>
                                <div class="mt-1 text-xs font-semibold text-slate-400">{{ $order->phone }} · {{ $order->created_at->format('Y-m-d H:i') }}</div>
                            </td>
                            <td>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-600">
                                    {{ optional($order->posSale)->number ?: 'POS' }}
                                </span>
                            </td>
                            <td><span class="badge-status-{{ $order->status }}">{{ $statusLabels[$order->status] ?? $order->status }}</span></td>
                            <td class="font-black">{{ number_format($order->total, 2) }} ج.م</td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="btn-secondary">عرض</a>
                                    @if($order->posSale)
                                        <a href="{{ route('admin.pos.show', $order->posSale) }}" class="btn-secondary">POS</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state">لا توجد طلبات يدوية حالياً.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $manualOrders->appends(request()->except('manual_page'))->links() }}</div>
        </div>
    </section>
</div>
@endsection
