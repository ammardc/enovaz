@props([
    'variant' => 'primary',
    'size' => 'md',
    'disabled' => false,
    'type' => 'button',
])

@php
$baseClasses = 'inline-flex items-center justify-center font-medium transition-colors duration-200 focus-visible:outline-2 focus-visible:outline-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

$variantClasses = match($variant) {
    'primary' => 'bg-[#6b2cdd] text-white hover:bg-[#5520c0] focus-visible:outline-[#6b2cdd]',
    'secondary' => 'bg-gray-100 text-gray-900 hover:bg-gray-200 focus-visible:outline-gray-400',
    'success' => 'bg-[#10b981] text-white hover:bg-[#059669] focus-visible:outline-[#10b981]',
    'error' => 'bg-[#ef4444] text-white hover:bg-[#dc2626] focus-visible:outline-[#ef4444]',
    'ghost' => 'bg-transparent text-gray-700 hover:bg-gray-100 focus-visible:outline-gray-300',
    default => 'bg-[#6b2cdd] text-white hover:bg-[#5520c0]',
};

$sizeClasses = match($size) {
    'sm' => 'px-3 py-1.5 text-sm rounded-md',
    'md' => 'px-4 py-2.5 text-base rounded-lg',
    'lg' => 'px-6 py-3 text-lg rounded-lg',
    'xl' => 'px-8 py-4 text-lg rounded-xl',
    default => 'px-4 py-2.5 text-base rounded-lg',
};
@endphp

<button 
    type="{{ $type }}"
    {{ $attributes->merge(['class' => "$baseClasses $variantClasses $sizeClasses"]) }}
    @if($disabled) disabled @endif
>
    {{ $slot }}
</button>
