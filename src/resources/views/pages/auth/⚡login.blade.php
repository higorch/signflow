<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function render()
    {
        return $this->view()->layout('layouts::auth')->title($this->pageTitle);
    }

    #[Computed]
    public function pageTitle()
    {
        return 'Login';
    }

    public function rendered()
    {
        $this->dispatch('errors-login', errors: $this->getErrorBag());

        $this->errorToastErrorBag();
        $this->resetErrorBag();
    }

    public function submit()
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $credentials = [
            'email' => $this->email,
            'password' => $this->password,
            'status' => 'active',
        ];

        if (!Auth::attempt($credentials, $this->remember)) {
            $this->dispatch('notify', msg: "E-mail ou senha incorretos.", type: "error");
            return;
        }

        request()->session()->regenerate();

        $user = Auth::user();

        if (!$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();

            return $this->redirectRoute('verification.notice', navigate: true);
        }

        // só entra no painel se estiver verificado
        return $this->redirectRoute('panel.dashboard.index', navigate: true);
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
        <h2 class="text-xl font-semibold text-white text-center mb-4">Entrar na plataforma</h2>

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

            <!-- Password -->
            <div x-data="{ show: false }">
                <label class="label-input-basic text-white" for="password">Senha</label>
                <div class="relative">
                    <!-- Ícone esquerda -->
                    <i class="las la-lock absolute left-3 top-1/2 -translate-y-1/2 text-white/50 text-sm"></i>
                    <!-- Input -->
                    <input :type="show ? 'text' : 'password'" class="input-basic pl-9 pr-10" wire:model="password" id="password">
                    <!-- Toggle olho -->
                    <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-white/50 hover:text-white transition">
                        <i :class="show ? 'las la-eye-slash' : 'las la-eye'"></i>
                    </button>
                    @error('password') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Forgot -->
            <div class="text-right">
                <a href="{{ route('auth.forgot.password') }}" wire:navigate class="text-sm text-text-muted hover:text-text">
                    Esqueceu?
                </a>
            </div>

            <!-- Submit -->
            <a href="#" wire:click.prevent="submit" class="w-full btn-primary">
                Entrar
            </a>

            <!-- Divider -->
            <div class="flex items-center gap-2 my-2">
                <div class="flex-1 h-px bg-white/15"></div>
                <span class="text-white/50 text-xs">ou</span>
                <div class="flex-1 h-px bg-white/15"></div>
            </div>

        </div>

        <!-- Register -->
        <p class="text-center text-sm">
            <a href="{{ route('auth.signup') }}" wire:navigate class="text-text-muted hover:text-text">
                Criar uma conta grátis
            </a>
        </p>

    </div>

</div>