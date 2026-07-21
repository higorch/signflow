<x-mail::message>
# Olá, {{ $signer->user->name }}!

O processo digital abaixo foi **retornado para rascunho** pelo responsável.

**Referência:** {{ $process->reference }}

@if(filled($process->title))
**Título:** {{ $process->title }}
@endif

Este processo não está mais disponível para assinatura.

<x-mail::subcopy>
Este e-mail foi enviado por **{{ config('app.name') }}**.
</x-mail::subcopy>
</x-mail::message>