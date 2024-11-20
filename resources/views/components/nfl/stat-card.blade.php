<div class="{{ $bgColor }} rounded-lg p-4">
    <h3 class="text-sm font-medium {{ $textColor }} mb-2">{{ $title }}</h3>
    <p class="text-lg font-bold {{ $textColor }}">{{ $value }}</p>
    @if (!empty($subtitle))
        <p class="text-xs {{ $textColor }} mt-1">{{ $subtitle }}</p>
    @endif
</div>
