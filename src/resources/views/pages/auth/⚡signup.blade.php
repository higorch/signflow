<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public $form = [
        'name' => '',
        'email' => '',
        'password' => '',
        'password_confirmation' => ''
    ];

    public function render()
    {
        return $this->view([
            'pageTitle' => $this->pageTitle
        ])->layout('layouts::auth')->title($this->pageTitle);
    }

    public function exception($e, $stopPropagation)
    {
        if ($e instanceof ValidationException) {
            $this->dispatch('errors-signup', errors: $this->getErrorBag());
            $this->errorToastErrorBag();
        }
    }

    #[Computed]
    public function pageTitle()
    {
        return 'Cadastre-se grátis';
    }

    public function submit()
    {
        $this->validate();

        try {
            $user = User::create([
                'role' => 'customer',
                'name' => $this->form['name'],
                'email' => data_get($this->form, 'email') ?: null,
                'password' => Hash::make($this->form['password']),
                'status' => 'active'
            ]);

            // limpa o formulário
            $this->reset('form');

            // já logar o usuário
            Auth::login($user);
            request()->session()->regenerate();

            if (!$user->hasVerifiedEmail()) {
                $user->sendEmailVerificationNotification();

                return $this->redirectRoute('verification.notice', navigate: true);
            }

            return redirect()->route('panel.dashboard.index');
        } catch (\Exception $e) {
            Log::channel('auth')->error('Erro ao registrar usuário', ['message' => $e->getMessage()]);
            $this->dispatch('notify', msg: "Não foi possível registrar.", type: "error");
        }
    }

    protected function prepareForValidation($attributes): array
    {
        return $attributes;
    }

    protected function rules()
    {
        return [
            'form.name' => [
                'required',
                'min:5',
                'max:40',
                function ($attribute, $value, $fail) {
                    $value = trim($value);

                    if (preg_match('/[\x{1F300}-\x{1FAFF}]/u', $value)) {
                        $fail(__('validation.sem_emoji'));
                        return;
                    }

                    $parts = preg_split('/\s+/', $value);

                    foreach ($parts as $part) {
                        if (! preg_match('/^[A-Za-zÀ-ÿ]+$/u', $part)) {
                            $fail(__('validation.sem_caracteres'));
                            return;
                        }
                    }
                }
            ],
            'form.email' => [
                'required',
                'email:filter',
                function ($attribute, $value, $fail) {
                    if (blank($value)) return;
                    if (User::where('email_hash', hmac_hash($value))->exists()) {
                        $fail(__('validation.unique'));
                    }
                }
            ],
            'form.password' => [
                'required',
                'min:8',              // deve ter no mínimo 8 caracteres
                'regex:/[a-z]/',      // deve conter pelo menos uma letra minúscula
                'regex:/[A-Z]/',      // deve conter pelo menos uma letra maiúscula
                'regex:/[0-9]/',      // deve conter pelo menos um número
                'regex:/[@$!%*#?&]/', // deve conter pelo menos um caractere especial
            ],
            'form.password_confirmation' => [
                'required',
                'same:form.password'
            ],
        ];
    }

    protected function messages()
    {
        return [
            'form.password_confirmation.same' => __('validation.confirmed'),
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

            <!-- Nome -->
            <div>
                <label class="label-input-basic text-white" for="name">Nome</label>
                <div class="relative">
                    <div class="relative">
                        <i class="las la-user absolute left-3 top-1/2 -translate-y-1/2 text-white/50 text-sm"></i>
                        <input type="text" class="input-basic pl-9" wire:model="form.name" id="name">
                        <x-global.limit-input :limit="40" :model="'form.name'" :stop="true" :align="'center'" />
                    </div>
                    @error('form.name') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Email -->
            <div>
                <label class="label-input-basic text-white" for="email">E-mail</label>
                <div class="relative">
                    <i class="las la-envelope absolute left-3 top-1/2 -translate-y-1/2 text-white/50 text-sm"></i>
                    <input type="email" class="input-basic pl-9" wire:model="form.email" id="email">
                    @error('form.email') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Password -->
            <div x-data="{ show: false }">
                <label class="label-input-basic text-white" for="password">Senha</label>
                <div class="relative">
                    <!-- Ícone esquerda -->
                    <i class="las la-lock absolute left-3 top-1/2 -translate-y-1/2 text-white/50 text-sm"></i>
                    <!-- Input -->
                    <input :type="show ? 'text' : 'password'" class="input-basic pl-9 pr-10" wire:model="form.password" id="password">
                    <!-- Toggle olho -->
                    <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-white/50 hover:text-white transition">
                        <i :class="show ? 'las la-eye-slash' : 'las la-eye'"></i>
                    </button>
                    @error('form.password') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- CONFIRMAR SENHA -->
            <div x-data="{ show: false }">
                <label class="label-input-basic text-white">Confirmar senha</label>
                <div class="relative">
                    <!-- Ícone esquerda -->
                    <i class="las la-lock absolute left-3 top-1/2 -translate-y-1/2 text-white/50 text-sm"></i>
                    <!-- Input -->
                    <input :type="show ? 'text':'password'" class="input-basic pl-9 pr-10" wire:model="form.password_confirmation" id="password_confirmation">
                    <!-- Toggle olho -->
                    <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-white/50 hover:text-white transition">
                        <i :class="show ? 'las la-eye-slash' : 'las la-eye'"></i>
                    </button>
                    @error('form.password_confirmation') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- SEGURANÇA DA SENHA -->
            <livewire:global.strong-password-verifier wire:model="form.password" />

            <!-- Submit -->
            <a href="#" wire:click.prevent="submit" class="w-full btn-primary mt-2">
                Cadastrar
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