<!-- components/table.blade.php -->
<table class="min-w-full bg-white border border-gray-200 divide-y divide-gray-200 shadow-md rounded-lg">
    <thead class="bg-gray-50">
    <tr>
        {{ $header }}
    </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
    {{ $body }}
    </tbody>
</table>
