<?php

use App\Models\Department;
use App\Models\User;
use App\Models\Process;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public ?string $processId = null;

    public array $form = [
        'email' => '',
        'name' => '',
        'cpf_cnpj' => '',
        'department' => ''
    ];

    public function render()
    {
        return $this->view([
            'departments' => $this->departments
        ]);
    }

    public function exception($e, $stopPropagation)
    {
        if ($e instanceof ValidationException) {
            $this->dispatch('errors-modal-process-signer', errors: $this->getErrorBag());
            $this->errorToastErrorBag();
        }
    }

    public function updatedFormEmail($value)
    {
        $this->resetErrorBag('form.email');

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) return null;

        $signer = User::where('email_hash', hmac_hash($value))->first();

        if ($signer) {
            $this->form = [
                'email' => $signer->email,
                'name' => $signer->name,
                'cpf_cnpj' => maskFormat('cpf_cnpj', $signer->cpf_cnpj),
                'department' => ''
            ];
        }
    }

    #[On('opened.modal-process-signer')]
    public function openModalSigner($payload)
    {
        $this->processId = $payload['processId'] ?? null;
    }

    #[Computed]
    public function process()
    {
        if (blank($this->processId)) return null;

        return Process::where('id', $this->processId)->first();
    }

    #[Computed]
    public function signer()
    {
        if (!filter_var(data_get($this->form, 'email'), FILTER_VALIDATE_EMAIL)) return null;

        return User::where('email_hash', hmac_hash(data_get($this->form, 'email')))->first();
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
            DB::transaction(function () {
                $signer = $this->signer;

                $data = [
                    'email' => $this->form['email'],
                    'name' => $this->form['name'],
                    'cpf_cnpj' => sanitizeSpecialCharacters($this->form['cpf_cnpj'], true),
                    'status' => $signer ? $signer->status : 'active'
                ];

                if (!$signer) {
                    $data['role'] = 'signer';
                    $data['password'] = Str::password(
                        length: 16,
                        letters: true,
                        numbers: true,
                        symbols: true,
                        spaces: false
                    );

                    $signer = User::create($data);
                }

                $exists = $this->process->signers()->where('user_id', $signer->id)->exists();

                if ($exists) {
                    $this->dispatch('notify', msg: 'Signatário já está vinculado a este processo.', type: 'info');
                } else {
                    $this->process->signers()->firstOrCreate([
                        'user_id' => $signer->id,
                        'status' => 'awaiting-signature',
                        'department_id' => $this->form['department']
                    ]);

                    $this->dispatch('notify', msg: 'Signatário adicionado com sucesso.', type: 'success');
                }
            });

            $this->reset('form');

            $this->js('$wire.$dispatch("close-modal", { ref: "modal-process-signer" })');
            $this->dispatch('refresh')->to('pages::panel.process.save');
        } catch (\Throwable $e) {
            Log::channel('process')->error('Erro ao adicionar signatário', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            $this->dispatch('notify', msg: 'Não foi possível adicionar.', type: 'error');
        }
    }

    protected function prepareForValidation($attributes)
    {
        return $attributes;
    }

    protected function rules(): array
    {
        $signer = $this->signer;

        return [
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
            'form.name' => [
                'min:5',
                'max:40',
                Rule::requiredIf(function () {
                    return filter_var(data_get($this->form, 'email'), FILTER_VALIDATE_EMAIL);
                }),
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
            'form.department' => [
                'required',
                Rule::exists('departments', 'id'),
            ],
            'form.cpf_cnpj' => [
                'cpf_ou_cnpj',
                Rule::requiredIf(function () {
                    return filter_var(data_get($this->form, 'email'), FILTER_VALIDATE_EMAIL);
                }),
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

<div wire:ignore.self class="fixed inset-0 overflow-y-auto bg-black/60 invisible" x-data="modal('modal-process-signer')" x-bind="events" :class="{'visible': open, 'invisible': !open}">

    <div class="flex items-center justify-center min-h-dvh p-6" @click.self="open = true">

        <div wire:loading.class="loading-box-fade" wire:target="submit" class="relative w-full max-w-2xl rounded-md shadow-lg bg-card" x-show="open" x-transition>

            <span class="absolute top-4 right-4 text-lg cursor-pointer text-text-muted hover:text-red-500" @click.prevent="open = false">
                <i class="las la-times"></i>
            </span>

            {{-- HEADER --}}
            <div class="flex items-center w-full p-4 border-b border-border/40">
                <p class="font-semibold text-lg text-text-soft">Adicionar Signatário</p>
            </div>

            {{-- BODY --}}
            <div wire:keydown.enter="submit" class="flex flex-col grow p-4">

                <div class="grid grid-cols-12 gap-3">

                    {{-- EMAIL --}}
                    <div class="relative col-span-full md:col-span-12 flex flex-col gap-1">
                        <label class="label-input-basic">E-mail</label>
                        <div wire:loading.class="loading-input" wire:target="form.email">
                            <input type="email" wire:model.live.debounce.500ms="form.email" class="input-basic">
                        </div>
                        @error('form.email') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
                    </div>

                    @if(filter_var($this->form['email'], FILTER_VALIDATE_EMAIL))

                    {{-- NAME --}}
                    <div class="relative col-span-full md:col-span-12 flex flex-col gap-1">
                        <label class="label-input-basic">Nome</label>
                        <div class="relative">
                            <input type="text" class="input-basic" wire:model="form.name">
                            <x-global.limit-input :limit="40" :model="'form.name'" :stop="true" :align="'center'" />
                        </div>
                        @error('form.name') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
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

                    @endif

                </div>

            </div>

            {{-- FOOTER --}}
            <div class="flex gap-4 w-full p-4 border-t border-border/40">
                <a href="#" @click.prevent="open = false" class="flex-1 btn-secondary">
                    <i class="las la-times text-lg"></i>Fechar
                </a>
                <a href="#" wire:click.prevent="submit" class="flex-1 btn-primary">
                    <i class="las la-plus text-lg"></i> Adicionar
                </a>
            </div>

        </div>

    </div>

</div>