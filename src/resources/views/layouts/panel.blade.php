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

<body x-data="panel" x-bind="events" class="antialiased bg-background">

    <div class="min-h-dvh overflow-x-hidden">

        <div x-show="mobileMenu" class="fixed inset-0 bg-black/40 z-40 lg:hidden" @click="mobileMenu=false"></div>

        <aside x-cloak :class="[mobileMenu ? 'translate-x-0' : '-translate-x-full lg:translate-x-0', menuOpen ? 'lg:w-64' : 'lg:w-20']" class="w-68.75 flex flex-col fixed top-0 left-0 z-50 h-dvh transform transition-all duration-300 overflow-hidden bg-sidebar">

            <div class="h-20.25 flex items-center justify-center border-b border-[#fada82]/5 px-4 shrink-0" title="Experiência renovada, Ecossistema Provenda">
                <img x-show="menuOpen || mobileMenu" src="{{ Vite::asset('resources/assets/images/logo-white.png') }}" class="w-full max-w-36">
                <img x-show="!menuOpen && !mobileMenu" src="{{ Vite::asset('resources/assets/images/icon-white.png') }}" class="w-9 h-auto">
            </div>

            <nav class="grow p-4 space-y-2 text-sm overflow-y-auto">

                <a href="{{ route('panel.dashboard.index') }}" wire:navigate class="flex items-center gap-3 px-4 py-2 rounded-lg font-medium transition-all duration-200 {{ request()->routeIs('panel.dashboard.*') ? 'bg-primary text-text-soft shadow-sm' : 'text-text-soft/80 hover:bg-white/10 backdrop-blur-md]' }}">
                    <i class="las la-chart-pie text-lg"></i>
                    <span class="whitespace-nowrap" x-show="menuOpen || mobileMenu" x-transition.opacity.duration.150ms>Dashboard</span>
                </a>

                <a href="{{ route('panel.processes.index') }}" wire:navigate class="flex items-center gap-3 px-4 py-2 rounded-lg font-medium transition-all duration-200 {{ request()->routeIs('panel.processes.*') ? 'bg-primary text-text-soft shadow-sm' : 'text-text-soft/80 hover:bg-white/10 backdrop-blur-md]' }}">
                    <i class="las la-file-alt text-lg"></i>
                    <span class="whitespace-nowrap" x-show="menuOpen || mobileMenu" x-transition.opacity.duration.150ms>Processos</span>
                </a>

                <a href="{{ route('panel.signers.index') }}" wire:navigate class="flex items-center gap-3 px-4 py-2 rounded-lg font-medium transition-all duration-200 {{ request()->routeIs('panel.signers.*') ? 'bg-primary text-text-soft shadow-sm' : 'text-text-soft/80 hover:bg-white/10 backdrop-blur-md]' }}">
                    <i class="las la-users text-lg"></i>
                    <span class="whitespace-nowrap" x-show="menuOpen || mobileMenu" x-transition.opacity.duration.150ms>Signatários</span>
                </a>

            </nav>

        </aside>

        <div x-cloak :class="menuOpen ? 'lg:ml-64' : 'lg:ml-20'" class="min-h-dvh flex flex-col transition-all duration-300">
            <livewire:global.panel-header />
            <main class="flex-1 flex flex-col px-6 lg:px-10 pb-10">{{ $slot }}</main>
        </div>

    </div>

    @livewireScripts
</body>

</html>