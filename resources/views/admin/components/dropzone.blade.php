@php
    $accept = $accept ?? 'image/png,image/jpeg,image/webp';
@endphp

<div class="space-y-2">
    <label class="text-sm font-bold">{{ $label ?? $name }}</label>
    <label class="dropzone-premium block cursor-pointer text-center p-5 border-dashed">
        <input type="file" name="{{ $name }}" accept="{{ $accept }}" class="hidden" @if(!empty($multiple)) multiple @endif>
        <p class="text-sm text-slate-600">اسحب الصورة هنا أو اضغط للاختيار</p>
        @if(!empty($hint))
            <p class="text-xs text-slate-500 mt-1">{{ $hint }}</p>
        @endif
    </label>

    @if(!empty($current))
        <div>
            <p class="text-xs text-slate-500 mb-1">الصورة الحالية</p>
            <img src="{{ $current }}" class="w-24 h-24 rounded-xl object-cover border border-slate-200">
        </div>
    @endif

    @error($name)
        <p class="text-xs text-rose-600">{{ $message }}</p>
    @enderror
</div>
