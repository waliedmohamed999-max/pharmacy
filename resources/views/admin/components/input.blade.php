@php
    $type = $type ?? 'text';
    $required = $required ?? false;
    $value = $value ?? old($name ?? '', '');
@endphp

<div class="space-y-1">
    <label class="text-sm font-bold">{{ $label ?? $name }}</label>
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        value="{{ $value }}"
        placeholder="{{ $placeholder ?? '' }}"
        class="input-premium"
        @if($required) required @endif
    >
    @if(!empty($hint))
        <p class="text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="text-xs text-rose-600">{{ $message }}</p>
    @enderror
</div>
