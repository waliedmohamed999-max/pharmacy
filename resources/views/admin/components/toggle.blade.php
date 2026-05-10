@php
    $checked = (bool) ($checked ?? false);
@endphp

<label class="card-premium px-3 py-2 flex items-center justify-between gap-3">
    <span class="text-sm font-bold">{{ $label ?? $name }}</span>
    <input type="checkbox" name="{{ $name }}" value="1" class="w-5 h-5 accent-sky-500" @checked($checked)>
</label>
