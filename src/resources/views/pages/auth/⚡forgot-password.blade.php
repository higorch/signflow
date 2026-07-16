<?php

use App\Notifications\Auth\ResetPasswordNotification;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $email = '';

    public function render()
    {
        return $this->view()->layout('layouts::auth')->title($this->pageTitle);
    }

    #[Computed]
    public function pageTitle()
    {
        return 'Recuperar senha';
    }

    public function rendered()
    {
        $this->dispatch('errors-forgot-password', errors: $this->getErrorBag());

        $this->errorToastErrorBag();
        $this->resetErrorBag();
    }

    public function submit()
    {
        $credentials = $this->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink($credentials, function ($user, $token) {
            $user->notify(new ResetPasswordNotification($token));
        });

        if ($status === Password::ResetLinkSent) {
            session()->flash('success', 'Por favor, verifique sua caixa de entrada ou pasta de spam no e-mail ' . $this->email  . ' para criar uma nova senha.');
            $this->reset('email');
        } else {
            session()->flash('warning', "Por favor, verifique sua identidade inserindo o endereço de e-mail associado à sua conta.");
        }
    }

    private function errorToastErrorBag()
    {
        $errors = $this->getErrorBag();
        $count = count($errors->getMessages());

        if ($count > 0) {
            $this->dispatch('notify', msg: $count === 1 ? __('app.one_filling_problem') : $count . ' ' . __('app.filling_problems'), type: 'error');
        }
    }
};
?>

<div class="flex flex-col w-full items-center justify-center">

    <!-- Logo -->
    <div class="flex justify-center mb-4">
        <img src="{{ Vite::asset('resources/assets/images/logo-blue.png') }}" alt="Sign Flow" class="h-10">
    </div>

    <div wire:loading.class="loading-box-fade" wire:keydown.enter="submit" class="relative w-full max-w-md rounded-md shadow-xl p-6 overflow-hidden border border-border bg-card">

        <!-- Title -->
        <h2 class="text-xl font-semibold text-white text-center mb-4">Recupere sua senha</h2>

        <!-- Form -->
        <div class="flex flex-col gap-3">

            <!-- Flash Mensagens -->
            @if (session()->has('success'))
            <div class="alert alert-success" role="alert">
                <span>{{ session('success') }}</span>
            </div>
            @endif

            @if (session()->has('warning'))
            <div class="alert alert-warning" role="alert">
                <span>{{ session('warning') }}</span>
            </div>
            @endif

            @if (session()->has('error'))
            <div class="alert alert-danger" role="alert">
                <span>{{ $errors->first() }}</span>
            </div>
            @endif

            <!-- Email -->
            <div>
                <label class="label-input-basic text-white" for="email">E-mail</label>
                <div class="relative">
                    <i class="las la-envelope absolute left-3 top-1/2 -translate-y-1/2 text-white/50 text-sm"></i>
                    <input type="email" class="input-basic pl-9" wire:model="email" id="email">
                    @error('email') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Submit -->
            <a href="#" wire:click.prevent="submit" class="w-full btn-primary">
                Enviar
            </a>

            <!-- Divider -->
            <div class="flex items-center gap-2 my-2">
                <div class="flex-1 h-px bg-white/15"></div>
                <span class="text-white/50 text-xs">ou</span>
                <div class="flex-1 h-px bg-white/15"></div>
            </div>

        </div>

        <p class="text-center text-sm">
            <a href="{{ route('auth.login') }}" wire:navigate class="text-text-muted hover:text-text">
                Fazer login
            </a>
        </p>

    </div>

</div>