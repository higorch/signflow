<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    {{-- @env('local')
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';">
    @endenv --}}
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? '' }} - {{ config('app.name') }}</title>
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

<body class="min-h-screen bg-background">

    <main class="mx-auto flex min-h-screen max-w-7xl items-center justify-center px-6">

        <div class="max-w-lg text-center">

            <div class="mb-6 text-8xl font-bold text-text">
                {{ $code ?? '404' }}
            </div>

            <h1 class="text-3xl font-semibold text-text-muted">
                {{ $title ?? 'Página não encontrada' }}
            </h1>

            <p class="mt-4 text-sm leading-relaxed text-text-muted">
                {{ $message ?? 'O conteúdo solicitado não existe ou não está mais disponível.' }}
            </p>

            <div class="mt-8">
                <a href="{{ route('auth.login') }}" class="inline-flex btn-primary">
                    <i class="las la-home text-lg"></i>
                    <span>Voltar para o início</span>
                </a>
            </div>

        </div>

    </main>

    @livewireScripts
</body>

</html>