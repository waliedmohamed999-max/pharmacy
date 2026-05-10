@extends('admin.layouts.app')

@section('page-title', 'صلاحيات المستخدمين')
@section('page-subtitle', 'نظام RBAC متكامل لتحديد الوصول لكل أقسام الصيدلية ERP')

@php
    $totalUsers = $users->count();
    $adminCount = $users->where('role', 'admin')->count();
    $staffCount = max(0, $totalUsers - $adminCount);
    $totalPermissions = count($permissions);
@endphp

@section('content')
<div class="space-y-5">
    <section class="grid gap-4 md:grid-cols-4">
        <div class="card-premium p-4">
            <div class="text-xs font-black text-slate-400">إجمالي المستخدمين</div>
            <div class="mt-2 text-3xl font-black text-slate-950">{{ number_format($totalUsers) }}</div>
        </div>
        <div class="card-premium p-4">
            <div class="text-xs font-black text-slate-400">مدراء النظام</div>
            <div class="mt-2 text-3xl font-black text-emerald-700">{{ number_format($adminCount) }}</div>
        </div>
        <div class="card-premium p-4">
            <div class="text-xs font-black text-slate-400">مستخدمون مخصصون</div>
            <div class="mt-2 text-3xl font-black text-sky-700">{{ number_format($staffCount) }}</div>
        </div>
        <div class="card-premium p-4">
            <div class="text-xs font-black text-slate-400">الصلاحيات المتاحة</div>
            <div class="mt-2 text-3xl font-black text-violet-700">{{ number_format($totalPermissions) }}</div>
        </div>
    </section>

    <section class="card-premium p-4">
        <div class="flex flex-col justify-between gap-3 lg:flex-row lg:items-center">
            <div>
                <h2 class="text-2xl font-black text-slate-950">مركز التحكم في الصلاحيات</h2>
                <p class="mt-1 text-sm font-bold text-slate-500">اختر المستخدم، طبق قالب دور جاهز، أو عدل الصلاحيات يدوياً حسب المسؤوليات.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="btn-secondary" data-check-visible>تحديد الظاهر</button>
                <button type="button" class="btn-secondary" data-uncheck-visible>إلغاء الظاهر</button>
                <input id="permissionSearch" class="input-premium w-full md:w-80" placeholder="ابحث عن صلاحية أو قسم...">
            </div>
        </div>
    </section>

    <div class="space-y-5">
        @foreach($users as $user)
            @php
                $selected = $user->role === 'admin' ? array_keys($permissions) : ($user->permissions_json ?? []);
                $selectedCount = count(array_intersect($selected, array_keys($permissions)));
                $roleMeta = $rolePresets[$user->role] ?? $rolePresets['staff'];
            @endphp
            <form method="POST" action="{{ route('admin.users.permissions.update') }}" class="permission-form card-premium overflow-hidden p-0" data-user-form>
                @csrf
                <input type="hidden" name="user_id" value="{{ $user->id }}">

                <div class="grid gap-4 border-b border-slate-100 bg-gradient-to-l from-emerald-50 to-white p-5 xl:grid-cols-[1fr_360px] xl:items-center">
                    <div class="flex items-center gap-4">
                        <div class="grid h-14 w-14 shrink-0 place-items-center rounded-3xl bg-emerald-600 text-xl font-black text-white shadow-lg shadow-emerald-600/20">
                            {{ mb_substr($user->name, 0, 1) }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="truncate text-2xl font-black text-slate-950">{{ $user->name }}</h3>
                            <p class="mt-1 text-sm font-bold text-slate-500">{{ $user->email }}</p>
                            <div class="mt-2 flex flex-wrap gap-2 text-xs font-black">
                                <span class="rounded-full bg-white px-3 py-1 text-emerald-700 ring-1 ring-emerald-100">{{ $roleMeta['label'] }}</span>
                                <span class="rounded-full bg-white px-3 py-1 text-slate-500 ring-1 ring-slate-100"><span data-selected-count>{{ $selectedCount }}</span> / {{ $totalPermissions }} صلاحية</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <label class="text-xs font-black text-slate-500">قالب الدور</label>
                        <select name="role" class="select-premium" data-role-select>
                            @foreach($rolePresets as $key => $preset)
                                <option value="{{ $key }}" data-permissions='@json($preset['permissions'])' @selected($user->role === $key)>
                                    {{ $preset['label'] }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs font-bold leading-5 text-slate-500" data-role-description>{{ $roleMeta['description'] }}</p>
                    </div>
                </div>

                <div class="grid gap-4 p-5 xl:grid-cols-[280px_1fr]">
                    <aside class="space-y-3">
                        <div class="rounded-3xl border border-slate-200 bg-white p-4">
                            <h4 class="font-black text-slate-950">إجراءات سريعة</h4>
                            <div class="mt-3 grid gap-2">
                                <button type="button" class="btn-secondary justify-center" data-apply-role>تطبيق قالب الدور</button>
                                <button type="button" class="btn-secondary justify-center" data-select-all>تحديد كل الصلاحيات</button>
                                <button type="button" class="btn-secondary justify-center" data-clear-all>إلغاء الكل</button>
                                <button class="btn-primary justify-center">حفظ الصلاحيات</button>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-amber-100 bg-amber-50 p-4 text-sm font-bold leading-7 text-amber-800">
                            دور المدير يحصل على وصول كامل تلقائياً. باقي الأدوار تعتمد على الصلاحيات المحددة هنا.
                        </div>
                    </aside>

                    <div class="grid gap-4 lg:grid-cols-2">
                        @foreach($permissionGroups as $groupKey => $group)
                            <section class="permission-group rounded-3xl border border-slate-200 bg-white p-4 shadow-sm" data-permission-group data-search="{{ \Illuminate\Support\Str::lower($group['label'].' '.$group['description'].' '.implode(' ', $group['items'])) }}">
                                <div class="mb-4 flex items-start justify-between gap-3">
                                    <div>
                                        <h4 class="text-lg font-black text-slate-950">{{ $group['label'] }}</h4>
                                        <p class="mt-1 text-xs font-bold leading-5 text-slate-500">{{ $group['description'] }}</p>
                                    </div>
                                    <label class="inline-flex shrink-0 cursor-pointer items-center gap-2 rounded-2xl bg-slate-50 px-3 py-2 text-xs font-black text-slate-600">
                                        <input type="checkbox" class="accent-emerald-600" data-group-toggle>
                                        الكل
                                    </label>
                                </div>

                                <div class="grid gap-2">
                                    @foreach($group['items'] as $key => $label)
                                        <label class="permission-item flex cursor-pointer items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-slate-50 px-3 py-2 transition hover:border-emerald-200 hover:bg-emerald-50" data-permission-item data-search="{{ \Illuminate\Support\Str::lower($key.' '.$label) }}">
                                            <span>
                                                <span class="block text-sm font-black text-slate-800">{{ $label }}</span>
                                                <span class="block text-[11px] font-bold text-slate-400">{{ $key }}</span>
                                            </span>
                                            <input type="checkbox" name="permissions[]" value="{{ $key }}" class="h-5 w-5 accent-emerald-600" data-permission-checkbox @checked(in_array($key, $selected, true))>
                                        </label>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>
                </div>
            </form>
        @endforeach
    </div>
</div>

<script>
(() => {
    const roleDescriptions = @json(collect($rolePresets)->mapWithKeys(fn ($preset, $key) => [$key => $preset['description']]));
    const allPermissions = @json(array_keys($permissions));
    const searchInput = document.getElementById('permissionSearch');

    const refreshForm = (form) => {
        const checked = form.querySelectorAll('[data-permission-checkbox]:checked').length;
        const count = form.querySelector('[data-selected-count]');
        if (count) count.textContent = checked;

        form.querySelectorAll('[data-permission-group]').forEach((group) => {
            const boxes = [...group.querySelectorAll('[data-permission-checkbox]')];
            const toggle = group.querySelector('[data-group-toggle]');
            if (!toggle || !boxes.length) return;
            const selected = boxes.filter((box) => box.checked).length;
            toggle.checked = selected === boxes.length;
            toggle.indeterminate = selected > 0 && selected < boxes.length;
        });
    };

    const applyRole = (form) => {
        const select = form.querySelector('[data-role-select]');
        const selected = select?.selectedOptions?.[0];
        const permissions = JSON.parse(selected?.dataset.permissions || '[]');
        const isAdmin = permissions.includes('*');
        form.querySelectorAll('[data-permission-checkbox]').forEach((box) => {
            box.checked = isAdmin || permissions.includes(box.value);
        });
        const desc = form.querySelector('[data-role-description]');
        if (desc && select) desc.textContent = roleDescriptions[select.value] || '';
        refreshForm(form);
    };

    document.querySelectorAll('[data-user-form]').forEach((form) => {
        refreshForm(form);

        form.querySelector('[data-role-select]')?.addEventListener('change', () => applyRole(form));
        form.querySelector('[data-apply-role]')?.addEventListener('click', () => applyRole(form));
        form.querySelector('[data-select-all]')?.addEventListener('click', () => {
            form.querySelectorAll('[data-permission-checkbox]').forEach((box) => box.checked = true);
            refreshForm(form);
        });
        form.querySelector('[data-clear-all]')?.addEventListener('click', () => {
            form.querySelectorAll('[data-permission-checkbox]').forEach((box) => box.checked = false);
            refreshForm(form);
        });
        form.querySelectorAll('[data-group-toggle]').forEach((toggle) => {
            toggle.addEventListener('change', () => {
                toggle.closest('[data-permission-group]')?.querySelectorAll('[data-permission-checkbox]').forEach((box) => box.checked = toggle.checked);
                refreshForm(form);
            });
        });
        form.querySelectorAll('[data-permission-checkbox]').forEach((box) => box.addEventListener('change', () => refreshForm(form)));
    });

    const filterVisible = () => {
        const term = (searchInput?.value || '').trim().toLowerCase();
        document.querySelectorAll('[data-permission-group]').forEach((group) => {
            let anyItem = false;
            group.querySelectorAll('[data-permission-item]').forEach((item) => {
                const matched = !term || item.dataset.search.includes(term) || group.dataset.search.includes(term);
                item.classList.toggle('hidden', !matched);
                anyItem = anyItem || matched;
            });
            group.classList.toggle('hidden', !anyItem);
        });
    };

    searchInput?.addEventListener('input', filterVisible);

    document.querySelector('[data-check-visible]')?.addEventListener('click', () => {
        document.querySelectorAll('[data-user-form]').forEach((form) => {
            form.querySelectorAll('[data-permission-item]:not(.hidden) [data-permission-checkbox]').forEach((box) => box.checked = true);
            refreshForm(form);
        });
    });

    document.querySelector('[data-uncheck-visible]')?.addEventListener('click', () => {
        document.querySelectorAll('[data-user-form]').forEach((form) => {
            form.querySelectorAll('[data-permission-item]:not(.hidden) [data-permission-checkbox]').forEach((box) => box.checked = false);
            refreshForm(form);
        });
    });
})();
</script>
@endsection
