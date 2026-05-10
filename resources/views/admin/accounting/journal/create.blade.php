@extends('admin.layouts.app')

@section('page-title', 'قيد يومي جديد')
@section('page-subtitle', 'إدخال قيد يدوي متوازن (مدين = دائن)')

@section('content')
<form action="{{ route('admin.accounting.journal.store') }}" method="POST" class="space-y-4">
    @csrf
    <section class="card-premium p-4 grid md:grid-cols-2 gap-3">
        <div>
            <label class="block text-xs text-slate-500 mb-1">تاريخ القيد</label>
            <input type="date" name="entry_date" class="input-premium" value="{{ date('Y-m-d') }}" required>
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">الوصف</label>
            <input type="text" name="description" class="input-premium">
        </div>
    </section>

    <section class="card-premium p-4">
        <h2 class="font-black mb-3">سطور القيد</h2>
        @for($i = 0; $i < 4; $i++)
            <div class="grid md:grid-cols-4 gap-3 mb-3">
                <select name="account_id[]" class="select-premium" required>
                    <option value="">اختر الحساب</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                    @endforeach
                </select>
                <input type="number" step="0.01" min="0" name="debit[]" class="input-premium" placeholder="مدين">
                <input type="number" step="0.01" min="0" name="credit[]" class="input-premium" placeholder="دائن">
                <input type="text" name="line_description[]" class="input-premium" placeholder="وصف السطر">
            </div>
        @endfor
    </section>

    <div class="flex justify-end">
        <button class="btn-primary">حفظ القيد</button>
    </div>
</form>
@endsection
