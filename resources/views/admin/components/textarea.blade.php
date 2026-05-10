@php
    $rows = $rows ?? 4;
    $value = $value ?? old($name ?? '', '');
@endphp

<div class="space-y-1">
    <label class="text-sm font-bold">{{ $label ?? $name }}</label>
    <textarea name="{{ $name }}" rows="{{ $rows }}" class="textarea-premium" placeholder="{{ $placeholder ?? '' }}">{{ $value }}</textarea>
    @if(!empty($hint))
        <p class="text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="text-xs text-rose-600">{{ $message }}</p>
    @enderror
</div>
