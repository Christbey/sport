<!-- components/radio-button.blade.php -->
<div class="mb-4">
    <div class="flex items-center">
        <input id="{{ $id }}"
               name="{{ $name }}"
               type="radio"
               value="{{ $value }}"
               class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300"
                {{ $checked ? 'checked' : '' }}>
        <label for="{{ $id }}" class="ml-3 block text-sm text-gray-600 font-medium {{ $checked ? 'font-bold' : '' }}">
            {{ $label }}
        </label>
    </div>
</div>
