<!-- resources/views/components/alert.blade.php -->
@props(['color' => 'gray'])

<div {{ $attributes->merge(['class' => 'p-4 mb-4 text-sm text-' . $color . '-700 bg-' . $color . '-100 rounded-lg']) }} role="alert">
    {{ $slot }}
</div>
