<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public int $cooldown = 40;
    public bool $resent = false;

    public function render()
    {
        return $this->view([
            'pageTitle' => $this->pageTitle
        ])->layout('layouts::auth')->title($this->pageTitle);
    }

    #[Computed]
    public function pageTitle()
    {
        return 'Verificar seu e-mail';
    }

    public function mount()
    {
        $user = Auth::user();

        // direciona acesso se já verificado
        if ($user && $user->hasVerifiedEmail()) {
            return redirect()->route('panel.dashboard.index');
        }
    }

    public function resend()
    {
        if ($this->cooldown > 0) return;

        $user = Auth::user();

        if (!$user) return;

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('panel.dashboard.index');
        }

        $user->sendEmailVerificationNotification();

        session()->flash('success', 'E-mail reenviado com sucesso!');

        $this->cooldown = 40;
        $this->resent = true;
    }
};
?>

<div class="flex flex-col w-full items-center justify-center">

    <!-- Logo -->
    <div class="flex justify-center mb-4">
        <img src="{{ Vite::asset('resources/assets/images/logo-blue.png') }}" alt="Sign Flow" class="h-10">
    </div>

    <div x-data="countdownTimer($wire.entangle('cooldown'))" class="relative w-full max-w-md rounded-md shadow-xl p-6 overflow-hidden border border-border bg-card">

        <!-- Title -->
        <h2 class="text-xl font-semibold text-white text-center mb-4">{{ $pageTitle }}</h2>

        <!-- Alert -->
        @if ($resent)
        <div class="alert alert-success">
            <span>E-mail reenviado com sucesso! Verifique sua caixa de entrada.</span>
        </div>
        @else
        <div class="alert alert-warning">
            <span>Enviamos um link para seu e-mail. Clique nele para ativar sua conta.</span>
        </div>
        @endif

        <!-- Botão -->
        <div class="mt-4">
            <button type="button" wire:click="resend" wire:loading.attr="disabled" :disabled="disabled" class="w-full btn-primary">
                <span x-text="disabled ? `Reenviar em ${countdown}s` : 'Reenviar e-mail'"></span>
            </button>
        </div>

    </div>

</div>