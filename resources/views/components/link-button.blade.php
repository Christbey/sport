<!-- resources/views/components/link-button.blade.php -->
<a {{ $attributes->merge(['class' => 'inline-flex items-center justify-center px-6 py-3 text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors duration-200']) }}>
    {{ $slot }}
</a>