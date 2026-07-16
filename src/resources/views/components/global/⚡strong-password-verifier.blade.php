<?php

use Livewire\Attributes\Modelable;
use Livewire\Component;

new class extends Component
{
    #[Modelable]
    public $password = '';
};
?>

{{-- SEGURANÇA DA SENHA --}}
<div x-data="passwordSecurity($wire.entangle('password'))" class="col-span-full flex flex-col gap-4 rounded-md p-5 shadow-xl border border-border bg-background">

    <div class="flex justify-between items-center gap-2">
        <h4 class="font-semibold text-xs tracking-wide text-text/80 uppercase">
            Segurança da senha
        </h4>
        <div :class="{'hidden': !valid, 'block': valid}" class="hidden mt-2 font-black text-center text-xs tracking-wide uppercase text-emerald-400">
            Senha forte
        </div>
    </div>

    <div class="flex flex-col gap-1.5 text-sm">

        <div class="flex items-center justify-between">
            <span class="text-xs text-text/70">Mínimo de 8 caracteres</span>
            <span class="size-2.5 rounded-full" :class="hasMin ? 'bg-emerald-400' : 'bg-text/30'"></span>
        </div>

        <div class="flex items-center justify-between">
            <span class="text-xs text-text/70">Letra minúscula</span>
            <span class="size-2.5 rounded-full" :class="hasLower ? 'bg-emerald-400' : 'bg-text/30'"></span>
        </div>

        <div class="flex items-center justify-between">
            <span class="text-xs text-text/70">Letra maiúscula</span>
            <span class="size-2.5 rounded-full" :class="hasUpper ? 'bg-emerald-400' : 'bg-text/30'"></span>
        </div>

        <div class="flex items-center justify-between">
            <span class="text-xs text-text/70">Pelo menos 1 número</span>
            <span class="size-2.5 rounded-full" :class="hasNumber ? 'bg-emerald-400' : 'bg-text/30'"></span>
        </div>

        <div class="flex items-center justify-between">
            <span class="text-xs text-text/70">1 caractere especial (@$!%*#?&)</span>
            <span class="size-2.5 rounded-full" :class="hasSpecial ? 'bg-emerald-400' : 'bg-text/30'"></span>
        </div>

    </div>

</div>