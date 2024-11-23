<!-- resources/views/components/badge.blade.php -->
@props(['color' => 'info'])

<span {{ $attributes->merge(['class' => 'ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-' . $color . '-100 text-' . $color . '-800']) }}>
    {{ $slot }}
</span>
