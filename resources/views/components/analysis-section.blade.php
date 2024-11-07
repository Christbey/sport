<!-- resources/views/components/analysis-section.blade.php -->
@props(['title', 'items'])

<div class="bg-white shadow-lg rounded-lg p-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">{{ $title }}</h2>
    <ul class="list-disc pl-5 text-gray-700 space-y-2">
        @foreach($items as $label => $value)
            <li><strong>{{ $label }}:</strong> {{ $value ?? 'N/A' }}</li>
        @endforeach
    </ul>
</div>
