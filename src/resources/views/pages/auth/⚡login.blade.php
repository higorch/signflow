<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function render()
    {
        return $this->view([
            'pageTitle' => $this->pageTitle
        ])->layout('layouts::auth')->title($this->pageTitle);
    }

    public function exception($e, $stopPropagation)
    {
        if ($e instanceof ValidationException) {
            $this->dispatch('errors-login', errors: $this->getErrorBag());
            $this->errorToastErrorBag();
        }
    }

    #[Computed]
    public function pageTitle()
    {
        return 'Entrar na plataforma';
    }

    public function submit()
    {
        $this->validate();

        $user = User::query()->where('email_hash', hmac_hash($this->email))->whereIn('status', ['active'])->first();

        if ($user && Hash::check($this->password, $user->password)) {
            Auth::login($user, $this->remember);

            request()->session()->regenerate();

            $user = Auth::user();

            if (!$user->hasVerifiedEmail()) {
                $user->sendEmailVerificationNotification();

                return $this->redirectRoute('verification.notice', navigate: true);
            }

            return match ($user->role) {
                'root', 'admin', 'customer', 'signer' => redirect()->route('panel.dashboard.index'),
                default => tap(redirect()->route('auth.login'), function () {
                    Auth::logout();
                    request()->session()->invalidate();
                    request()->session()->regenerateToken();
                })
            };
        } else {
            $this->dispatch('notify', msg: 'Dados de acesso inválidos.', type: 'error');
        }
    }

    protected function prepareForValidation($attributes): array
    {
        return $attributes;
    }

    public function rules()
    {
        return [
            'email' => 'required|email',
            'password' => ['required'],
        ];
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
        <h2 class="text-xl font-semibold text-white text-center mb-4">{{ $pageTitle }}</h2>

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