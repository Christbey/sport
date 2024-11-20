<div class="bg-white rounded-lg shadow-lg overflow-hidden">
    <div class="{{ $headerColor }} px-6 py-4">
        <h2 class="text-lg font-semibold text-white">{{ $title }}</h2>
    </div>
    <div class="p-6 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                {{ $thead ?? '' }}
            </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
            {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
