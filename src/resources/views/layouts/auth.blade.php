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

<body x-data="auth" x-bind="events" class="antialiased">

    <div class="grid min-h-dvh grid-cols-12 overflow-hidden">

        <main class="col-span-full md:col-span-5 flex items-center justify-center bg-background lg:py-8 px-8 lg:px-12">
            {{ $slot }}
        </main>

        <div class="relative hidden overflow-hidden md:col-span-7 md:block">

            <img src="{{ Vite::asset('resources/assets/images/bg-login.jpg') }}" class="absolute inset-0 h-full w-full object-cover brightness-50 saturate-110">

            {{-- Escurece a imagem --}}
            <div class="absolute inset-0 bg-background/65"></div>

            {{-- Integração com a coluna do formulário --}}
            <div class="absolute inset-y-0 left-0 w-96 bg-linear-to-r from-background via-background/80 via-40% to-transparent"></div>

            {{-- Vinheta --}}
            <div class="absolute inset-0 bg-linear-to-t from-background/40 via-transparent to-background/20"></div>

            {{-- Glow azul sutil --}}
            <div class="absolute left-12 top-1/2 h-80 w-80 -translate-y-1/2 rounded-full bg-primary/10 blur-3xl"></div>

            <div class="absolute inset-0 flex items-center px-20">

                <div class="max-w-xl">
                    <span class="inline-flex rounded-full border border-primary/20 bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                        Gestão de Processos Digitais
                    </span>
                    <h1 class="mt-6 text-4xl lg:text-5xl font-bold leading-9 lg:leading-13 text-text">
                        Assine processos digitalmente com segurança.
                    </h1>
                    <p class="mt-6 text-lg lg:text-xl leading-6 lg:leading-8 text-text-muted">
                        Centralize documentos, acompanhe aprovações e realize
                        assinaturas eletrônicas em uma plataforma moderna e segura.
                    </p>
                </div>

            </div>

        </div>

    </div>

    @livewireScripts

</body>

</html>