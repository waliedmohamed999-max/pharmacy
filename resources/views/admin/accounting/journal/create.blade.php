@extends('admin.layouts.app')

@section('page-title', 'قيد يومي جديد')
@section('page-subtitle', 'إدخال قيد يدوي متوازن بحيث يساوي إجمالي المدين إجمالي الدائن')

@section('content')
<form id="journal-form" action="{{ route('admin.accounting.journal.store') }}" method="POST" class="space-y-5">
    @csrf

    <section class="rounded-[26px] border border-emerald-100 bg-gradient-to-l from-emerald-50 via-white to-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div class="text-right">
                <div class="text-xs font-black uppercase tracking-wide text-emerald-600">Manual Journal Entry</div>
                <h1 class="mt-1 text-3xl font-black text-slate-950">إنشاء قيد يومي</h1>
                <p class="mt-1 text-sm font-semibold text-slate-500">أدخل أطراف القيد، وسيتم احتساب التوازن والفرق قبل الحفظ.</p>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-right">
                    <div class="text-xs font-bold text-slate-500">إجمالي المدين</div>
                    <div id="total-debit" class="mt-1 text-base font-black text-slate-950">0.00</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-right">
                    <div class="text-xs font-bold text-slate-500">إجمالي الدائن</div>
                    <div id="total-credit" class="mt-1 text-base font-black text-slate-950">0.00</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-right">
                    <div class="text-xs font-bold text-slate-500">الفرق</div>
                    <div id="total-difference" class="mt-1 text-base font-black text-amber-600">0.00</div>
                </div>
                <div id="balance-badge" class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-right">
                    <div class="text-xs font-bold text-amber-700">الحالة</div>
                    <div class="mt-1 text-base font-black text-amber-700">غير مكتمل</div>
                </div>
            </div>
        </div>
    </section>

    <section class="card-premium p-5">
        <div class="grid gap-4 lg:grid-cols-[260px_1fr]">
            <div>
                <label class="mb-1 block text-xs font-black text-slate-500">تاريخ القيد</label>
                <input type="date" name="entry_date" class="input-premium" value="{{ old('entry_date', date('Y-m-d')) }}" required>
            </div>
            <div>
                <label class="mb-1 block text-xs font-black text-slate-500">وصف القيد</label>
                <input type="text" name="description" class="input-premium" value="{{ old('description') }}" placeholder="مثال: تسوية مصروف أو إثبات حركة مالية">
            </div>
        </div>
    </section>

    <section class="card-premium p-5">
        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="text-right">
                <h2 class="text-xl font-black text-slate-950">سطور القيد</h2>
                <p class="mt-1 text-sm font-semibold text-slate-500">كل سطر يجب أن يحتوي على حساب ومبلغ في المدين أو الدائن فقط.</p>
            </div>
            <button id="add-line" type="button" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-black text-emerald-700 transition hover:bg-emerald-100">
                إضافة سطر
            </button>
        </div>

        <div class="hidden rounded-2xl bg-slate-50 px-4 py-2 text-xs font-black text-slate-500 lg:grid lg:grid-cols-[1.25fr_.75fr_.75fr_1fr_44px] lg:gap-3">
            <div>الحساب</div>
            <div>مدين</div>
            <div>دائن</div>
            <div>وصف السطر</div>
            <div></div>
        </div>

        <div id="journal-lines" class="mt-3 space-y-3">
            @php
                $oldAccounts = old('account_id', ['', '', '', '']);
                $oldDebits = old('debit', ['', '', '', '']);
                $oldCredits = old('credit', ['', '', '', '']);
                $oldDescriptions = old('line_description', ['', '', '', '']);
                $lineCount = max(4, count($oldAccounts));
            @endphp
            @for($i = 0; $i < $lineCount; $i++)
                <div class="journal-line grid gap-3 rounded-2xl border border-slate-200 bg-white p-3 lg:grid-cols-[1.25fr_.75fr_.75fr_1fr_44px]">
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-500 lg:hidden">الحساب</label>
                        <select name="account_id[]" class="select-premium account-field">
                            <option value="">اختر الحساب</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" @selected((string) ($oldAccounts[$i] ?? '') === (string) $account->id)>
                                    {{ $account->code }} - {{ $account->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-500 lg:hidden">مدين</label>
                        <input type="number" step="0.01" min="0" name="debit[]" class="input-premium amount-field debit-field" value="{{ $oldDebits[$i] ?? '' }}" placeholder="0.00">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-500 lg:hidden">دائن</label>
                        <input type="number" step="0.01" min="0" name="credit[]" class="input-premium amount-field credit-field" value="{{ $oldCredits[$i] ?? '' }}" placeholder="0.00">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-500 lg:hidden">وصف السطر</label>
                        <input type="text" name="line_description[]" class="input-premium" value="{{ $oldDescriptions[$i] ?? '' }}" placeholder="وصف مختصر">
                    </div>
                    <button type="button" class="remove-line flex h-11 w-11 items-center justify-center rounded-2xl border border-red-100 bg-red-50 text-lg font-black text-red-600 transition hover:bg-red-100" title="حذف السطر">
                        ×
                    </button>
                </div>
            @endfor
        </div>

        <div class="mt-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div id="journal-hint" class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-right text-sm font-bold text-amber-700">
                أدخل على الأقل سطرين بمبالغ، واجعل إجمالي المدين مساوياً لإجمالي الدائن.
            </div>
            <button id="submit-journal" class="btn-primary px-7" disabled>حفظ القيد</button>
        </div>
    </section>
</form>

<template id="journal-line-template">
    <div class="journal-line grid gap-3 rounded-2xl border border-slate-200 bg-white p-3 lg:grid-cols-[1.25fr_.75fr_.75fr_1fr_44px]">
        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500 lg:hidden">الحساب</label>
            <select name="account_id[]" class="select-premium account-field">
                <option value="">اختر الحساب</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500 lg:hidden">مدين</label>
            <input type="number" step="0.01" min="0" name="debit[]" class="input-premium amount-field debit-field" placeholder="0.00">
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500 lg:hidden">دائن</label>
            <input type="number" step="0.01" min="0" name="credit[]" class="input-premium amount-field credit-field" placeholder="0.00">
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500 lg:hidden">وصف السطر</label>
            <input type="text" name="line_description[]" class="input-premium" placeholder="وصف مختصر">
        </div>
        <button type="button" class="remove-line flex h-11 w-11 items-center justify-center rounded-2xl border border-red-100 bg-red-50 text-lg font-black text-red-600 transition hover:bg-red-100" title="حذف السطر">
            ×
        </button>
    </div>
</template>

<script>
    (() => {
        const lines = document.getElementById('journal-lines');
        const template = document.getElementById('journal-line-template');
        const addLine = document.getElementById('add-line');
        const submit = document.getElementById('submit-journal');
        const totalDebit = document.getElementById('total-debit');
        const totalCredit = document.getElementById('total-credit');
        const totalDifference = document.getElementById('total-difference');
        const badge = document.getElementById('balance-badge');
        const hint = document.getElementById('journal-hint');

        const money = (value) => Number(value || 0).toLocaleString('ar-SA', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });

        const numberValue = (input) => Number.parseFloat(input.value || '0') || 0;

        const activeLinesCount = () => [...lines.querySelectorAll('.journal-line')].filter((line) => {
            const account = line.querySelector('.account-field').value;
            const debit = numberValue(line.querySelector('.debit-field'));
            const credit = numberValue(line.querySelector('.credit-field'));
            return account && (debit > 0 || credit > 0);
        }).length;

        const recalc = () => {
            let debit = 0;
            let credit = 0;
            lines.querySelectorAll('.debit-field').forEach((input) => debit += numberValue(input));
            lines.querySelectorAll('.credit-field').forEach((input) => credit += numberValue(input));

            const difference = Math.abs(debit - credit);
            const balanced = debit > 0 && credit > 0 && difference < 0.005 && activeLinesCount() >= 2;

            totalDebit.textContent = money(debit);
            totalCredit.textContent = money(credit);
            totalDifference.textContent = money(difference);
            submit.disabled = !balanced;

            badge.className = balanced
                ? 'rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-right'
                : 'rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-right';
            badge.innerHTML = balanced
                ? '<div class="text-xs font-bold text-emerald-700">الحالة</div><div class="mt-1 text-base font-black text-emerald-700">متوازن</div>'
                : '<div class="text-xs font-bold text-amber-700">الحالة</div><div class="mt-1 text-base font-black text-amber-700">غير متوازن</div>';

            hint.className = balanced
                ? 'rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-right text-sm font-bold text-emerald-700'
                : 'rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-right text-sm font-bold text-amber-700';
            hint.textContent = balanced
                ? 'القيد متوازن وجاهز للحفظ.'
                : 'أدخل على الأقل سطرين بمبالغ، واجعل إجمالي المدين مساوياً لإجمالي الدائن.';
        };

        const bindLine = (line) => {
            const debit = line.querySelector('.debit-field');
            const credit = line.querySelector('.credit-field');

            debit.addEventListener('input', () => {
                if (numberValue(debit) > 0) {
                    credit.value = '';
                }
                recalc();
            });
            credit.addEventListener('input', () => {
                if (numberValue(credit) > 0) {
                    debit.value = '';
                }
                recalc();
            });
            line.querySelector('.account-field').addEventListener('change', recalc);
            line.querySelector('.remove-line').addEventListener('click', () => {
                if (lines.querySelectorAll('.journal-line').length > 2) {
                    line.remove();
                    recalc();
                }
            });
        };

        addLine.addEventListener('click', () => {
            const fragment = template.content.cloneNode(true);
            const line = fragment.querySelector('.journal-line');
            lines.appendChild(fragment);
            bindLine(line);
            recalc();
        });

        lines.querySelectorAll('.journal-line').forEach(bindLine);
        recalc();
    })();
</script>
@endsection
