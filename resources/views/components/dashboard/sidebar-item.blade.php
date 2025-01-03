@props([
    'route' => null,
    'icon' => '',
    'label' => '',
    'hasDropdown' => false,
    'dropdownId' => null
])

@php
    $isActive = $route ? request()->routeIs($route) : false;
    $baseClasses = 'flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group';
@endphp

@if($hasDropdown)
    <div x-data="{ open: false }">
        <button type="button"
                @click="open = !open"
                class="{{ $baseClasses }}"
                aria-controls="{{ $dropdownId }}">
            <x-dashboard.sidebar-icon :name="$icon"/>
            <span class="flex-1 ml-3 text-left whitespace-nowrap">{{ $label }}</span>
            <svg xmlns="http://www.w3.org/2000/svg"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke-width="1.5"
                 stroke="currentColor"
                 class="w-4 h-4 transition-transform duration-200"
                 :class="{ 'rotate-180': open }">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
            </svg>
        </button>
        <ul x-show="open"
            x-collapse
            class="py-2 space-y-2">
            {{ $slot }}
        </ul>
    </div>
@else
    @if($route)
        <a href="{{ route($route) }}"
           class="{{ $baseClasses }}">
            <x-dashboard.sidebar-icon :name="$icon"/>
            <span class="ml-3">{{ $label }}</span>
        </a>
    @endif
@endif