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

<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900">
<x-banner/>

<!-- Top Navigation -->
<x-dashboard.top-nav/>

<!-- Sidebar -->
<x-dashboard.sidebar/>

<!-- Main Content -->
<main class="p-4 md:ml-64 h-auto pt-20">
    {{ $slot }}
</main>

@livewireScripts
</body>
</html>