<x-mail::message :url="$url">
# Olá, Recupere sua senha!
 
Clique no botão para criar a nova senha.

<x-mail::button :url="$url">
    Redefinir nova senha
</x-mail::button>

<x-mail::subcopy>
Este e-mail foi enviado por **{{ config('app.name') }}**.
</x-mail::subcopy>
</x-mail::message>