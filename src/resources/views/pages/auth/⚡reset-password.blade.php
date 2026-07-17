<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $email = '';
    public string $token = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function render()
    {
        return $this->view([
            'pageTitle' => $this->pageTitle
        ])->layout('layouts::auth')->title($this->pageTitle);
    }

    public function mount()
    {
        $this->token = request()->route('token');
        $this->email = request()->query('email');

        if (!AuthService::validateResetPasswordToken($this->email, $this->token)) {
            abort(403);
        }
    }

    public function exception($e, $stopPropagation)
    {
        if ($e instanceof ValidationException) {
            $this->dispatch('errors-reset-password', errors: $this->getErrorBag());
            $this->errorToastErrorBag();
        }
    }

    #[Computed]
    public function pageTitle()
    {
        return 'Nova senha';
    }

    public function submit()
    {
        $data = $this->validate();

        try {
            if (!AuthService::validateResetPasswordToken($data['email'], $data['token'])) {
                session()->flash('warning', 'Não foi possível redefinir sua senha. O link pode estar inválido ou expirado.');

                return;
            }

            $user = User::query()->where('email_hash', hmac_hash($data['email']))->where('status', 'active')->first();

            if (!$user) {
                session()->flash('warning', 'Não foi possível redefinir sua senha.');

                return;
            }

            $user->forceFill([
                'password' => Hash::make($data['password']),
            ])->setRememberToken(Str::random(60));

            $user->save();

            DB::table('password_reset_tokens')->where('email_hash', $user->email_hash)->delete();

            session()->flash('success', 'Sua senha foi redefinida com sucesso. Agora você já pode fazer login.');

            $this->reset('password', 'password_confirmation');

            return $this->redirect(route('auth.login'), navigate: true);
        } catch (\Throwable $e) {
            Log::channel('auth')->error('Erro na tentativa de redefinir senha', [
                'message' => $e->getMessage(),
            ]);

            session()->flash('warning', 'Não foi possível redefinir sua senha no momento. Tente novamente em alguns instantes.');
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
            'token' => 'required',
            'password' => [
                'required',
                'min:5',
                'regex:/[a-z]/', // Obriga ter pelo menos UMA letra minúscula (a até z)
                'regex:/[A-Z]/',  // Obriga ter pelo menos UMA letra maiúscula (A até Z
                'regex:/[0-9]/', // Obriga ter pelo menos UM número (0 até 9)
                'regex:/[@$!%*#?&]/', // Obriga ter pelo menos UM caractere especial "@ $ ! % * # ? &"
            ],
            'password_confirmation' => [
                'required',
                'same:password',
            ],
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
                    @error('password') <span @mouseover="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- CONFIRMAR SENHA -->
            <div x-data="{ show: false }">
                <label class="label-input-basic text-white">Confirmar senha</label>
                <div class="relative">
                    <!-- Ícone esquerda -->
                    <i class="las la-lock absolute left-3 top-1/2 -translate-y-1/2 text-white/50 text-sm"></i>
                    <!-- Input -->
                    <input :type="show ? 'text':'password'" class="input-basic pl-9 pr-10" wire:model="password_confirmation" id="password_confirmation">
                    <!-- Toggle olho -->
                    <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-white/50 hover:text-white transition">
                        <i :class="show ? 'las la-eye-slash' : 'las la-eye'"></i>
                    </button>
                    @error('password_confirmation') <span @mouseover="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- SEGURANÇA DA SENHA -->
            <livewire:global.strong-password-verifier wire:model="password" />

            <!-- Submit -->
            <a href="#" wire:click.prevent="submit" class="w-full btn-primary mt-2">
                Salvar
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