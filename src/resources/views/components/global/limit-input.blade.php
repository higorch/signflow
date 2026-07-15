@props(['limit', 'model', 'stop' => false, 'align' => 'bottom'])

@php
$align = match ($align) {
    'top' => 'top-2',
    'bottom' => 'bottom-2',
    'center' => 'top-1/2 -translate-y-1/2'
};
@endphp

<div x-data="limitInput({{ $limit }}, '{{ $model }}', {{ $stop ? 1 : 0 }})" x-cloak class="absolute right-2 {{ $align }} flex items-center gap-1 px-1 py-0.5 w-14 justify-center rounded-md border border-[#2c3446] bg-[#1f2738]/90 backdrop-blur-xs shadow-sm">

    <span x-text="length" :class="{
        'text-[#b8b8b8]': length < limit * 0.8,
        'text-[#e4c34b]': length >= limit * 0.8 && length < limit,
        'text-[#d66a6a]': length >= limit
    }" class="text-[10px] font-bold tracking-wide">
    </span>

    <span class="text-[10px] font-medium text-[#e3e3e3]/40">/</span>

    <span x-text="limit" class="text-[10px] font-bold tracking-wide text-[#e3e3e3]/50"></span>

</div>