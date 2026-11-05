@props([
    'hoverable' => false,
])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 bg-white p-6 shadow-sm ' . ($hoverable ? 'hover:shadow-md transition-shadow duration-200' : '')]) }}>
    {{ $slot }}
</div>
