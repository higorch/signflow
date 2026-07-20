<?php

use App\Models\User;
use App\Models\Department;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public User $user;

    public array $form = [
        'name' => '',
        'email' => '',
        'cpf_cnpj' => '',
        'status' => 'active',
        'department' => ''
    ];

    public function render()
    {
        return $this->view([
            'pageTitle' => $this->pageTitle,
            'departments' => $this->departments
        ])->title($this->pageTitle);
    }

    public function exception($e, $stopPropagation)
    {
        if ($e instanceof ValidationException) {
            $this->dispatch('errors-signer-save', errors: $this->getErrorBag());
            $this->errorToastErrorBag();
        }
    }

    public function mount(User $user)
    {
        $this->user = $user;

        $this->form = [
            'department' => $user->department_id,
            'name' => $user->name,
            'email' => $user->email,
            'cpf_cnpj' => $user->cpf_cnpj,
            'status' => $user->status,
        ];
    }

    #[On('refresh')]
    public function refresh() {}

    #[Computed]
    public function pageTitle()
    {
        return 'Editar Signatário';
    }

    #[Computed]
    public function departments()
    {
        return Department::get();
    }

    public function submit()
    {
        $this->validate();

        try {
            $this->user->update([
                'department_id' => $this->form['department'],
                'email' => $this->form['email'],
                'name' => $this->form['name'],
                'cpf_cnpj' => sanitizeSpecialCharacters($this->form['cpf_cnpj'], true),
                'status' => $this->form['status'],
            ]);

            session()->flash('success', 'Signatário salvo com sucesso.');

            return $this->redirectRoute('panel.signers.edit', [
                'user' => $this->user->ulid,
            ], navigate: true);
        } catch (\Throwable $e) {
            Log::channel('signer')->error('Erro ao salvar signatário', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            $this->dispatch('notify', msg: 'Não foi possível salvar.', type: 'error');
        }
    }

    protected function prepareForValidation($attributes): array
    {
        return $attributes;
    }

    protected function rules(): array
    {
        $signer = $this->user;

        return [
            'form.status' => 'required|in:active,disabled',
            'form.department' => [
                'required',
                Rule::exists('departments', 'id'),
            ],
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

                    foreach (preg_split('/\s+/', $value) as $part) {
                        if (! preg_match('/^[A-Za-zÀ-ÿ]+$/u', $part)) {
                            $fail(__('validation.sem_caracteres'));
                            return;
                        }
                    }
                },
            ],
            'form.email' => [
                'required',
                'email:filter',
                function ($attribute, $value, $fail) use ($signer) {
                    if (! $value) return;

                    $user = User::where('email_hash', hmac_hash($value))->first();

                    if ($user && (! $signer || $user->isNot($signer))) {
                        $fail(__('validation.unique'));
                    }
                },
            ],
            'form.cpf_cnpj' => [
                'required',
                'cpf_ou_cnpj',
                function ($attribute, $value, $fail) use ($signer) {
                    if (!$value) return;

                    $user = User::where(
                        'cpf_cnpj_hash',
                        hmac_hash($value, true, true)
                    )->first();

                    if ($user && (! $signer || $user->isNot($signer))) {
                        $fail(__('validation.unique'));
                    }
                },
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

<div class="flex-1 flex flex-col">

    @if (session('success'))
    <div class="alert alert-success flex items-center justify-between mb-3">
        <div class="flex items-start gap-2">
            <div class="alert-icon"><i class="las la-check-circle"></i></div>
            <div class="alert-content leading-normal">{{ session('success') }}</div>
        </div>
        <a href="#" @click.prevent="$el.closest('.alert').remove()" class="px-2 py-1 border border-emerald-400/40 rounded text-emerald-300 hover:text-emerald-200 hover:border-emerald-300 transition text-[11px] tracking-wide uppercase">Fechar</a>
    </div>
    @endif

    {{-- CABEÇALHO --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-5 mb-3">
        <div class="flex items-center gap-4">
            <a href="{{ route('panel.signers.index') }}" wire:navigate class="inline-flex items-center justify-center rounded-md border border-[#394150]/30 bg-[#394150]/5 px-3 py-2 text-text-soft transition hover:bg-surface-hover">
                <i class="las la-angle-left text-base"></i>
            </a>
            <h3 class="text-sm md:text-lg font-semibold tracking-wide uppercase text-text-soft">{{ $pageTitle }}</h3>
        </div>
        <div class="flex items-center justify-between gap-3">
            <a href="#" wire:click.prevent="submit" class="flex-1 md:w-auto inline-flex items-center justify-center gap-1.5 rounded-md bg-primary px-6 py-3 text-xs font-semibold uppercase tracking-wide text-text-soft shadow-lg transition hover:brightness-110">
                <i class="las la-save text-lg"></i>
                Salvar
            </a>
        </div>
    </div>

    {{-- FORMULÁRIO --}}
    <div class="grow">

        <div class="grid grid-cols-12 gap-3 rounded-md p-4 border border-border bg-card shadow-xl">

            {{-- NAME --}}
            <div class="relative col-span-full md:col-span-6 flex flex-col gap-1">
                <label class="label-input-basic">Nome</label>
                <div class="relative">
                    <input type="text" class="input-basic" wire:model="form.name">
                    <x-global.limit-input :limit="40" :model="'form.name'" :stop="true" :align="'center'" />
                </div>
                @error('form.name') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
            </div>

            {{-- EMAIL --}}
            <div class="relative col-span-full md:col-span-6 flex flex-col gap-1">
                <label class="label-input-basic">E-mail</label>
                <div wire:loading.class="loading-input" wire:target="form.email">
                    <input type="email" wire:model="form.email" class="input-basic">
                </div>
                @error('form.email') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
            </div>

            {{-- DEPARTAMENTO --}}
            <div class="relative col-span-full md:col-span-6 flex flex-col gap-1">
                <label class="label-input-basic">Departamento</label>
                <select x-data="choices($wire.entangle('form.department'), '---', '', 'auto', true)">
                    @foreach($departments as $department)
                    <option value="{{ $department->id }}">{{ $department->title }}</option>
                    @endforeach
                </select>
                @error('form.department') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
            </div>

            {{-- CPF/CNPJ --}}
            <div class="relative col-span-full md:col-span-6 flex flex-col gap-1">
                <label class="label-input-basic">CPF / CNPJ</label>
                <input type="text" wire:model="form.cpf_cnpj" class="input-basic" x-data="mask" data-inputmask="'mask': ['999.999.999-99', '99.999.999/9999-99'], 'keepStatic': true">
                @error('form.cpf_cnpj') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
            </div>

            {{-- STATUS --}}
            <div class="relative col-span-full md:col-span-12 flex flex-col gap-1">
                <label class="label-input-basic">Status</label>
                <select x-data="choices($wire.entangle('form.status'), '---', '', 'auto', false)">
                    <option value="active">Ativo</option>
                    <option value="disabled">Inativo</option>
                </select>
                @error('form.status') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
            </div>

        </div>

    </div>

    {{-- AÇÕES --}}
    <div class="flex flex-col-reverse gap-4 md:flex-row md:items-center md:justify-between mt-3">
        <a href="{{ route('panel.signers.index') }}" wire:navigate class="text-center text-[11px] uppercase tracking-wide text-text-soft/50 transition hover:text-text-soft"><i class="las la-angle-left text-xs"></i> Voltar</a>
    </div>

</div>