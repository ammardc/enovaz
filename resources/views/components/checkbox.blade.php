@props([
    'label' => null,
    'checked' => false,
    'error' => null,
])

<div class="flex items-center gap-2">
    <input 
        type="checkbox"
        {{ $attributes->merge(['class' => 'w-4 h-4 rounded border-gray-300 text-[#6b2cdd] focus:ring-[#6b2cdd]']) }}
        @if($checked) checked @endif
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
