@props([
    'label' => null,
    'error' => null,
])

<div class="w-full">
    @if($label)
        <label {{ $attributes->merge(['class' => 'block text-sm font-medium text-gray-700 mb-2']) }}>
            {{ $label }}
        </label>
    @endif
    
    <textarea 
        {{ $attributes->merge(['class' => 'w-full px-3 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-500 transition-colors duration-200 focus:border-[#6b2cdd] focus:outline-none focus:ring-2 focus:ring-[#f3e8ff] ' . ($error ? 'border-red-500' : '')]) }}
    ></textarea>
    
    @if($error)
        <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
    @endif
</div>
