@extends('admin.layouts.app')

@section('page-title', 'جهات الاتصال المالية')
@section('page-subtitle', 'العملاء والموردون وربطهم بالحركة المالية')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <section class="card-premium p-4 xl:col-span-2">
        <h2 class="text-lg font-black mb-3">الجهات</h2>
        <div class="table-wrap">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>النوع</th>
                        <th>الهاتف</th>
                        <th>الرصيد الافتتاحي</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($contacts as $contact)
                    <tr>
                        <td>{{ $contact->name }}</td>
                        <td>{{ $contact->type }}</td>
                        <td>{{ $contact->phone ?: '-' }}</td>
                        <td>{{ number_format($contact->opening_balance, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="empty-state">لا توجد جهات اتصال.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $contacts->links() }}</div>
    </section>

    <section class="card-premium p-4">
        <h2 class="text-lg font-black mb-3">إضافة جهة</h2>
        <form action="{{ route('admin.accounting.contacts.store') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs text-slate-500 mb-1">الاسم</label>
                <input type="text" name="name" class="input-premium" required>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">النوع</label>
                <select name="type" class="select-premium">
                    <option value="customer">عميل</option>
                    <option value="vendor">مورد</option>
                    <option value="both">عميل ومورد</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">الهاتف</label>
                <input type="text" name="phone" class="input-premium">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">البريد الإلكتروني</label>
                <input type="email" name="email" class="input-premium">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">الرصيد الافتتاحي</label>
                <input type="number" step="0.01" min="0" name="opening_balance" class="input-premium" value="0">
            </div>
            <button class="btn-primary w-full">حفظ</button>
        </form>
    </section>
</div>
@endsection
