@php
    $selected = $selected ?? old($name ?? '', '');
    $options = $options ?? [];
@endphp

<div class="space-y-1">
    <label class="text-sm font-bold">{{ $label ?? $name }}</label>
    <select name="{{ $name }}" class="select-premium" @if(!empty($multiple)) multiple @endif>
        @if(empty($multiple))
            <option value="">{{ $placeholder ?? 'اختر...' }}</option>
        @endif
        @foreach($options as $optValue => $optText)
            <option value="{{ $optValue }}" @selected((string)$selected === (string)$optValue)>{{ $optText }}</option>
        @endforeach
    </select>
    @error($name)
        <p class="text-xs text-rose-600">{{ $message }}</p>
    @enderror
</div>
