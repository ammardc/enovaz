@props([
    'variant' => 'info',
])

@php
$variantClasses = match($variant) {
    'success' => 'bg-[#f0fdf4] border-[#d1fae5] text-[#059669]',
    'error' => 'bg-[#fef2f2] border-[#fee2e2] text-[#dc2626]',
    'warning' => 'bg-[#fffbeb] border-[#fef3c7] text-[#d97706]',
    'info' => 'bg-[#eff6ff] border-[#dbeafe] text-[#1d4ed8]',
    default => 'bg-[#eff6ff] border-[#dbeafe] text-[#1d4ed8]',
};
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg border px-4 py-3 flex gap-3 items-start $variantClasses"]) }}>
    {{ $slot }}
</div>
