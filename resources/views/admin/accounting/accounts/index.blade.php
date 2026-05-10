@extends('admin.layouts.app')

@section('page-title', 'شجرة الحسابات')
@section('page-subtitle', 'إدارة الحسابات المحاسبية وربطها هرميًا')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <section class="card-premium p-4 xl:col-span-2">
        <h2 class="text-lg font-black mb-3">الشجرة الحالية</h2>
        <div class="space-y-2 text-sm">
            @forelse($roots as $root)
                <div class="card-premium p-3">
                    <div class="font-black">{{ $root->code }} - {{ $root->name }}</div>
                    @foreach($root->children as $child)
                        <div class="ps-6 mt-2">
                            <div class="font-semibold">{{ $child->code }} - {{ $child->name }}</div>
                            @foreach($child->children as $sub)
                                <div class="ps-6 text-slate-600">{{ $sub->code }} - {{ $sub->name }}</div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @empty
                <div class="empty-state">لا توجد حسابات بعد.</div>
            @endforelse
        </div>
    </section>

    <section class="card-premium p-4">
        <h2 class="text-lg font-black mb-3">إضافة حساب</h2>
        <form action="{{ route('admin.accounting.accounts.store') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs text-slate-500 mb-1">الحساب الأب</label>
                <select name="parent_id" class="select-premium">
                    <option value="">بدون (حساب رئيسي)</option>
                    @foreach($allAccounts as $a)
                        <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">الكود</label>
                <input type="text" name="code" class="input-premium" required>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">اسم الحساب</label>
                <input type="text" name="name" class="input-premium" required>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">النوع</label>
                <select name="type" class="select-premium" required>
                    <option value="asset">أصول</option>
                    <option value="liability">التزامات</option>
                    <option value="equity">حقوق ملكية</option>
                    <option value="revenue">إيرادات</option>
                    <option value="expense">مصروفات</option>
                </select>
            </div>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" checked>
                <span>نشط</span>
            </label>
            <button class="btn-primary w-full">حفظ</button>
        </form>
    </section>
</div>
@endsection
