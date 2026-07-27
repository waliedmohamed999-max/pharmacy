@extends('admin.layouts.app')

@section('page-title', 'جهات الاتصال المالية')
@section('page-subtitle', 'العملاء والموردون وربطهم بالحركة المالية')

@section('content')
<section class="rounded-[26px] border border-emerald-100 bg-gradient-to-l from-emerald-50 via-white to-white p-6 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="text-right">
            <div class="text-xs font-black uppercase tracking-wide text-emerald-600">Financial Contacts</div>
            <h1 class="mt-1 text-3xl font-black text-slate-950">جهات الاتصال المالية</h1>
            <p class="mt-1 text-sm font-semibold text-slate-500">إدارة العملاء والموردين واستخدامهم في الفواتير والتحصيل والسداد.</p>
        </div>
        <div class="grid grid-cols-2 gap-3 text-right sm:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3"><div class="text-xs font-bold text-slate-500">كل الجهات</div><div class="mt-1 text-sm font-black text-slate-950">{{ number_format($summary['total']) }}</div></div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3"><div class="text-xs font-bold text-slate-500">عملاء</div><div class="mt-1 text-sm font-black text-emerald-700">{{ number_format($summary['customers']) }}</div></div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3"><div class="text-xs font-bold text-slate-500">موردون</div><div class="mt-1 text-sm font-black text-blue-700">{{ number_format($summary['vendors']) }}</div></div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3"><div class="text-xs font-bold text-slate-500">رصيد افتتاحي</div><div class="mt-1 text-sm font-black text-slate-950">{{ number_format($summary['opening_balance'], 2) }}</div></div>
        </div>
    </div>
</section>

<div class="grid grid-cols-1 gap-5 xl:grid-cols-[1fr_420px]">
    <section class="card-premium overflow-hidden p-0">
        <div class="border-b border-slate-100 p-5">
            <form method="GET" class="grid gap-3 lg:grid-cols-[1.3fr_.8fr_.8fr_auto_auto]">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="input-premium" placeholder="بحث بالاسم، الهاتف، البريد، الرقم الضريبي">
                <select name="type" class="select-premium">
                    <option value="">كل الأنواع</option>
                    <option value="customer" @selected(($filters['type'] ?? '') === 'customer')>عميل</option>
                    <option value="vendor" @selected(($filters['type'] ?? '') === 'vendor')>مورد</option>
                    <option value="both" @selected(($filters['type'] ?? '') === 'both')>عميل ومورد</option>
                </select>
                <select name="status" class="select-premium">
                    <option value="">كل الحالات</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>نشط</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>غير نشط</option>
                </select>
                <button class="btn-primary">تطبيق</button>
                <a href="{{ route('admin.accounting.contacts.index') }}" class="btn-secondary text-center">إعادة</a>
            </form>
        </div>
        <div class="table-wrap">
            <table class="table-premium">
                <thead><tr><th>الاسم</th><th>النوع</th><th>الهاتف</th><th>البريد</th><th>الرقم الضريبي</th><th>الرصيد الافتتاحي</th><th>الحالة</th></tr></thead>
                <tbody>
                @forelse($contacts as $contact)
                    @php
                        $typeLabel = ['customer' => 'عميل', 'vendor' => 'مورد', 'both' => 'عميل ومورد'][$contact->type] ?? $contact->type;
                    @endphp
                    <tr>
                        <td class="font-black text-slate-900">{{ $contact->name }}</td>
                        <td>{{ $typeLabel }}</td>
                        <td>{{ $contact->phone ?: '-' }}</td>
                        <td>{{ $contact->email ?: '-' }}</td>
                        <td>{{ $contact->tax_number ?: '-' }}</td>
                        <td class="font-black">{{ number_format($contact->opening_balance, 2) }}</td>
                        <td><span class="rounded-full px-3 py-1 text-xs font-black {{ $contact->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $contact->is_active ? 'نشط' : 'غير نشط' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-state">لا توجد جهات اتصال.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $contacts->links() }}</div>
    </section>

    <section class="card-premium p-5">
        <div class="mb-4 text-right">
            <h2 class="text-xl font-black text-slate-950">إضافة جهة</h2>
            <p class="mt-1 text-sm font-semibold text-slate-500">استخدمها لاحقاً في الفواتير والسداد والتحصيل.</p>
        </div>
        <form action="{{ route('admin.accounting.contacts.store') }}" method="POST" class="space-y-3">
            @csrf
            <input type="hidden" name="is_active" value="0">
            <div><label class="mb-1 block text-xs font-black text-slate-500">الاسم</label><input type="text" name="name" class="input-premium" value="{{ old('name') }}" required></div>
            <div><label class="mb-1 block text-xs font-black text-slate-500">النوع</label><select name="type" class="select-premium"><option value="customer">عميل</option><option value="vendor">مورد</option><option value="both">عميل ومورد</option></select></div>
            <div class="grid gap-3 md:grid-cols-2">
                <div><label class="mb-1 block text-xs font-black text-slate-500">الهاتف</label><input type="text" name="phone" class="input-premium" value="{{ old('phone') }}"></div>
                <div><label class="mb-1 block text-xs font-black text-slate-500">البريد الإلكتروني</label><input type="email" name="email" class="input-premium" value="{{ old('email') }}"></div>
            </div>
            <div class="grid gap-3 md:grid-cols-2">
                <div><label class="mb-1 block text-xs font-black text-slate-500">الرقم الضريبي</label><input type="text" name="tax_number" class="input-premium" value="{{ old('tax_number') }}"></div>
                <div><label class="mb-1 block text-xs font-black text-slate-500">المدينة</label><input type="text" name="city" class="input-premium" value="{{ old('city') }}"></div>
            </div>
            <div><label class="mb-1 block text-xs font-black text-slate-500">العنوان</label><input type="text" name="address" class="input-premium" value="{{ old('address') }}"></div>
            <div><label class="mb-1 block text-xs font-black text-slate-500">الرصيد الافتتاحي</label><input type="number" step="0.01" min="0" name="opening_balance" class="input-premium" value="{{ old('opening_balance', 0) }}"></div>
            <label class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-800">
                <span>جهة نشطة</span>
                <input type="checkbox" name="is_active" value="1" class="h-5 w-5 accent-emerald-600" checked>
            </label>
            <button class="btn-primary w-full">حفظ الجهة</button>
        </form>
    </section>
</div>
@endsection
