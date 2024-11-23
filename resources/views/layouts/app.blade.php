<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet"/>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-100 h-full overflow-hidden">
<x-banner/>

<div class="flex flex-col h-full">
    @livewire('navigation-menu')

    @isset($header)
        <header class="bg-white shadow">
            <div class="px-4 py-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
    @endisset

    <main class="flex-1 overflow-auto">
        
        {{ $slot }}
    </main>
</div>

@stack('modals')
@livewireScripts
</body>
</html>