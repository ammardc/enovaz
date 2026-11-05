@props([
    'label' => null,
    'error' => null,
])

<div class="flex items-center gap-2">
    <input 
        type="radio"
        {{ $attributes->merge(['class' => 'w-4 h-4 border-gray-300 text-[#6b2cdd] focus:ring-[#6b2cdd]']) }}
    >
    
    @if($label)
        <label class="text-sm font-medium text-gray-700">
            {{ $label }}
        </label>
    @endif
</div>

@if($error)
    <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
@endif
