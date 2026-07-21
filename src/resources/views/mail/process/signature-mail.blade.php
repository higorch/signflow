<x-mail::message :url="$url">
# Olá, {{ $signer->user->name }}!

Você recebeu um **processo digital** para assinatura.

**Referência:** {{ $process->reference }}

@if(filled($process->title))
**Título:** {{ $process->title }}
@endif

<x-mail::button :url="$url">
Assinar processo
</x-mail::button>

<x-mail::subcopy>
Este e-mail foi enviado por **{{ config('app.name') }}**.
</x-mail::subcopy>
</x-mail::message>