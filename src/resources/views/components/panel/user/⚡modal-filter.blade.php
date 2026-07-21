<?php

use App\Models\Department;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public $fields = [
        'name' => '',
        'email' => '',
        'status' => '',
        'cpf_cnpj' => '',
        'departments' => '',
    ];

    public function render()
    {
        return $this->view([
            'departments' => $this->departments
        ]);
    }

    #[On('opened.modal-user-filter')]
    public function openModalUserFilter(array $payload)
    {
        $this->fields = array_merge($this->fields, data_get($payload, 'fields'));
    }

    #[Computed]
    public function departments()
    {
        $auth = Auth::user();

        $user = hmac_hash($auth->role) === hmac_hash('customer') ? $auth : $auth->linkedCustomers->first();

        $ids = $user->internalUsers->pluck('department_id')->push($user->department_id)->filter()->unique();

        return Department::whereIn('id', $ids)->get();
    }

    public function submit()
    {
        $this->dispatch('set-filter-fields', fields: $this->fields)->to('pages::panel.user.index');
        $this->js('$wire.$dispatch("close-modal", { ref: "modal-user-filter" })');
    }
};
?>

<div wire:ignore.self class="fixed inset-0 overflow-y-auto bg-black/60 invisible" x-data="modal('modal-user-filter')" :class="{'visible': open, 'invisible': !open}" x-bind="events">
    <div class="min-h-dvh">
        <div wire:loading.class="loading-box-fade" class="flex flex-col fixed transition-all duration-200 w-full md:w-5/12 h-dvh shadow-lg bg-card" :class="{'right-0 opacity-100': open, '-right-full opacity-0': !open}">

            <span class="absolute top-4 right-4 text-lg cursor-pointer text-text-muted hover:text-red-500" @click.prevent="open = false">
                <i class="las la-times"></i>
            </span>

            {{-- HEADER --}}
            <div class="shrink-0 flex items-center w-full p-4 border-b border-border/40">
                <p class="font-semibold text-lg text-text-soft">Filtrar Usuários</p>
            </div>

            {{-- BODY --}}
            <div class="flex-1 min-h-0 p-4 overflow-y-auto">

                <div class="grid grid-cols-12 gap-3">

                    {{-- STATUS --}}
                    <div class="relative col-span-12 md:col-span-12 flex flex-col gap-1">
                        <label class="label-input-basic">Status</label>
                        <select x-data="choices($wire.entangle('fields.status'), 'Todos', '', 'auto', true)">
                            <option value="">Todos</option>
                            <option value="active">Ativo</option>
                            <option value="disabled">Inativo</option>
                        </select>
                    </div>

                    {{-- DEPARTAMENTOS --}}
                    <div class="relative col-span-12 md:col-span-12 flex flex-col gap-1">
                        <label class="label-input-basic">Departamentos</label>
                        <select x-data="choices($wire.entangle('fields.departments'), '---', '', 'auto', true)" multiple>
                            @foreach($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- NOME --}}
                    <div class="relative col-span-12 flex flex-col gap-1">
                        <label class="label-input-basic">Nome</label>
                        <input type="text" wire:model.defer="fields.name" class="input-basic">
                    </div>

                    {{-- EMAIL --}}
                    <div class="relative col-span-12 flex flex-col gap-1">
                        <label class="label-input-basic">E-mail</label>
                        <input type="text" wire:model.defer="fields.email" class="input-basic">
                    </div>

                    {{-- CPF/CNPJ --}}
                    <div class="relative col-span-full md:col-span-12 flex flex-col gap-1">
                        <label class="label-input-basic">CPF / CNPJ</label>
                        <input type="text" wire:model="fields.cpf_cnpj" class="input-basic" x-data="mask" data-inputmask="'mask': ['999.999.999-99', '99.999.999/9999-99'], 'keepStatic': true">
                    </div>

                </div>

            </div>

            {{-- FOOTER --}}
            <div class="shrink-0 flex gap-4 w-full p-4 border-t border-border/40">
                <a href="#" @click.prevent="open = false" class="flex-1 btn-secondary">
                    <i class="las la-times text-lg"></i>Fechar
                </a>
                <a href="#" wire:click.prevent="submit" class="flex-1 btn-primary">
                    <i class="las la-filter text-lg"></i>Aplicar Filtros
                </a>
            </div>

        </div>
    </div>
</div>