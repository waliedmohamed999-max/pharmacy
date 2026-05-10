<div class="admin-toast-stack">
    @if(session('success'))
        <div class="admin-toast success" data-toast>
            <span class="badge-success">نجاح</span>
            <div class="text-sm">{{ session('success') }}</div>
            <button class="mr-auto text-slate-400" data-toast-close>✕</button>
        </div>
    @endif

    @if(session('error'))
        <div class="admin-toast error" data-toast>
            <span class="badge-danger">خطأ</span>
            <div class="text-sm">{{ session('error') }}</div>
            <button class="mr-auto text-slate-400" data-toast-close>✕</button>
        </div>
    @endif

    @if(session('info'))
        <div class="admin-toast info" data-toast>
            <span class="badge-soft">معلومة</span>
            <div class="text-sm">{{ session('info') }}</div>
            <button class="mr-auto text-slate-400" data-toast-close>✕</button>
        </div>
    @endif
</div>
