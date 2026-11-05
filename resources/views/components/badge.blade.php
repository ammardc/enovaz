@props([
    'variant' => 'primary',
])

@php
$variantClasses = match($variant) {
    'primary' => 'bg-[#f3e8ff] text-[#6b2cdd]',
    'success' => 'bg-[#d1fae5] text-[#059669]',
    'warning' => 'bg-[#fef3c7] text-[#d97706]',
    'error' => 'bg-[#fee2e2] text-[#dc2626]',
    'info' => 'bg-[#dbeafe] text-[#1d4ed8]',
    default => 'bg-[#f3e8ff] text-[#6b2cdd]',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold gap-1 $variantClasses"]) }}>
    {{ $slot }}
</span>
