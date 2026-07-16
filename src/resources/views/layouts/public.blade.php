<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    {{-- @env('local')
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';">
    @endenv --}}
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? '' }} • {{ config('app.name') }}</title>
    {{-- Favicon --}}
    <link rel="icon" href="{{ Vite::asset('resources/assets/images/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ Vite::asset('resources/assets/images/favicon.png') }}">
    @livewireStyles
    @vite(['resources/src/css/app.css', 'resources/src/js/app.js'])
    <style>
        [x-cloak] {
            display: none !important
        }
    </style>
</head>

<body x-data="public" x-bind="events" class="antialiased bg-background">

    <main class="flex-1 flex flex-col px-6 lg:px-10 pb-10">{{ $slot }}</main>
    
    @livewireScripts
</body>

</html>