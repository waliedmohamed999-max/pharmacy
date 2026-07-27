@extends('admin.layouts.app')

@section('page-title', 'شجرة الحسابات')
@section('page-subtitle', 'إدارة الحسابات المحاسبية وربطها هرمياً')

@section('content')
@php
    $typeLabels = [
        'asset' => 'أصول',
        'liability' => 'التزامات',
        'equity' => 'حقوق ملكية',
        'revenue' => 'إيرادات',
        'expense' => 'مصروفات',
    ];
@endphp

<section class="rounded-[26px] border border-emerald-100 bg-gradient-to-l from-emerald-50 via-white to-white p-6 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="text-right">
            <div class="text-xs font-black uppercase tracking-wide text-emerald-600">Chart of Accounts</div>
            <h1 class="mt-1 text-3xl font-black text-slate-950">شجرة الحسابات</h1>
            <p class="mt-1 text-sm font-semibold text-slate-500">إدارة الحسابات الرئيسية والفرعية المستخدمة في القيود والتقارير المالية.</p>
        </div>
        <div class="grid grid-cols-2 gap-3 text-right sm:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3"><div class="text-xs font-bold text-slate-500">كل الحسابات</div><div class="mt-1 text-sm font-black text-slate-950">{{ number_format($summary['total']) }}</div></div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3"><div class="text-xs font-bold text-slate-500">نشطة</div><div class="mt-1 text-sm font-black text-emerald-700">{{ number_format($summary['active']) }}</div></div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3"><div class="text-xs font-bold text-slate-500">نظامية</div><div class="mt-1 text-sm font-black text-blue-700">{{ number_format($summary['system']) }}</div></div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3"><div class="text-xs font-bold text-slate-500">رئيسية</div><div class="mt-1 text-sm font-black text-slate-950">{{ number_format($roots->count()) }}</div></div>
        </div>
    </div>
</section>

<div class="grid grid-cols-1 gap-5 xl:grid-cols-[1fr_420px]">
    <section class="card-premium p-5">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="text-right">
                <h2 class="text-xl font-black text-slate-950">الشجرة الحالية</h2>
                <p class="mt-1 text-sm font-semibold text-slate-500">استعراض الحسابات حسب الهيكل الهرمي.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach($typeLabels as $key => $label)
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ $label }}: {{ number_format((int) ($summary['types'][$key] ?? 0)) }}</span>
                @endforeach
            </div>
        </div>

        <div class="space-y-3">
            @forelse($roots as $root)
                <div class="rounded-3xl border border-slate-200 bg-white p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="text-right">
                            <div class="text-lg font-black text-slate-950">{{ $root->code }} - {{ $root->name }}</div>
                            <div class="mt-1 text-xs font-bold text-slate-500">{{ $typeLabels[$root->type] ?? $root->type }} · {{ $root->is_active ? 'نشط' : 'غير نشط' }} · {{ $root->is_system ? 'حساب نظامي' : 'حساب مخصص' }}</div>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">{{ $root->children->count() }} فرعي</span>
                    </div>

                    @if($root->children->count())
                        <div class="mt-4 grid gap-2">
                            @foreach($root->children as $child)
                                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="font-black text-slate-800">{{ $child->code }} - {{ $child->name }}</div>
                                        <span class="text-xs font-bold text-slate-500">{{ $typeLabels[$child->type] ?? $child->type }}</span>
                                    </div>
                                    @if($child->children->count())
                                        <div class="mt-2 grid gap-1 border-r-2 border-emerald-100 pr-4">
                                            @foreach($child->children as $sub)
                                                <div class="flex items-center justify-between rounded-xl bg-white px-3 py-2 text-sm">
                                                    <span class="font-semibold text-slate-700">{{ $sub->code }} - {{ $sub->name }}</span>
                                                    <span class="text-xs font-bold text-slate-400">{{ $sub->is_active ? 'نشط' : 'غير نشط' }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="empty-state">لا توجد حسابات بعد.</div>
            @endforelse
        </div>
    </section>

    <section class="card-premium p-5">
        <div class="mb-4 text-right">
            <h2 class="text-xl font-black text-slate-950">إضافة حساب</h2>
            <p class="mt-1 text-sm font-semibold text-slate-500">أضف حساباً رئيسياً أو فرعياً داخل الشجرة.</p>
        </div>
        <form action="{{ route('admin.accounting.accounts.store') }}" method="POST" class="space-y-3">
            @csrf
            <input type="hidden" name="is_active" value="0">
            <div>
                <label class="mb-1 block text-xs font-black text-slate-500">الحساب الأب</label>
                <select name="parent_id" class="select-premium">
                    <option value="">بدون (حساب رئيسي)</option>
                    @foreach($allAccounts as $account)
                        <option value="{{ $account->id }}" @selected((string) old('parent_id') === (string) $account->id)>{{ $account->code }} - {{ $account->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid gap-3 md:grid-cols-2">
                <div><label class="mb-1 block text-xs font-black text-slate-500">الكود</label><input type="text" name="code" class="input-premium" value="{{ old('code') }}" required></div>
                <div><label class="mb-1 block text-xs font-black text-slate-500">النوع</label><select name="type" class="select-premium" required>@foreach($typeLabels as $key => $label)<option value="{{ $key }}" @selected(old('type') === $key)>{{ $label }}</option>@endforeach</select></div>
            </div>
            <div><label class="mb-1 block text-xs font-black text-slate-500">اسم الحساب</label><input type="text" name="name" class="input-premium" value="{{ old('name') }}" required></div>
            <label class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-800">
                <span>حساب نشط</span>
                <input type="checkbox" name="is_active" value="1" class="h-5 w-5 accent-emerald-600" checked>
            </label>
            <button class="btn-primary w-full">حفظ الحساب</button>
        </form>
    </section>
</div>
@endsection
