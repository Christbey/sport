<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if ($uniqueId = request()->cookie('unique_id'))
        <meta name="x-unique-id" content="{{ $uniqueId }}">
    @endif

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    {!! $head ?? '' !!}
</head>

<body class="font-sans antialiased bg-gray-100 flex flex-col h-full">
<x-banner/>
@livewire('navigation-menu')

@isset($header)
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
            {{ $header }}
        </div>
    </header>
@endisset

<main class="flex-1 overflow-auto">
    {{ $slot }}
    @include('components.footer')
</main>

@livewireScripts
</body>
</html>
