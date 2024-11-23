<!-- resources/views/components/card.blade.php -->
<div {{ $attributes->merge(['class' => 'bg-white rounded-lg shadow-md overflow-hidden']) }}>
    {{ $slot }}
</div>
