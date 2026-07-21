<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public $fields = [
        'status' => '',
        'signer' => '',
        'categories' => '',
        'period' => [
            'from' => '',
            'to' => '',
        ]
    ];

    public function render()
    {
        return $this->view([
            'categories' => $this->categories,
            'signers' => $this->signers,
        ]);
    }

    #[On('opened.modal-process-filter')]
    public function openModalProcessFilter(array $payload)
    {
        $this->fields = array_merge($this->fields, data_get($payload, 'fields'));
    }

    #[Computed]
    public function categories()
    {
        $user = Auth::user();

        $ownerIds = $user->role_hash === hmac_hash('customer') ? $user->internalUsers()->pluck('users.id')->push($user->id) : collect([$user->id]);

        return Category::whereHas('processes', function ($query) use ($ownerIds) {
            $query->whereIn('owner_id', $ownerIds);
        })->orderBy('title')->get();
    }

    #[Computed]
    public function signers()
    {
        $user = Auth::user();

        $ownerIds = $user->role_hash === hmac_hash('customer') ? $user->internalUsers()->pluck('users.id')->push($user->id) : collect([$user->id]);

        return User::where('role_hash', hmac_hash('signer'))->whereHas('processSigners.process', function ($query) use ($ownerIds) {
            $query->whereIn('owner_id', $ownerIds);
        })->orderBy('name')->get();
    }

    public function submit()
    {
        $this->dispatch('set-filter-fields', fields: $this->fields)->to('pages::panel.process.index');
        $this->js('$wire.$dispatch("close-modal", { ref: "modal-process-filter" })');
    }
};
?>

<div wire:ignore.self class="fixed inset-0 overflow-y-auto bg-black/60 invisible" x-data="modal('modal-process-filter')" :class="{'visible': open, 'invisible': !open}" x-bind="events">
    <div class="min-h-dvh">
        <div wire:loading.class="loading-box-fade" wire:target.except="fields.period" class="flex flex-col fixed transition-all duration-200 w-full md:w-5/12 h-dvh shadow-lg bg-card" :class="{'right-0 opacity-100': open, '-right-full opacity-0': !open}">

            <span class="absolute top-4 right-4 text-lg cursor-pointer text-text-muted hover:text-red-500" @click.prevent="open = false">
                <i class="las la-times"></i>
            </span>

            {{-- HEADER --}}
            <div class="shrink-0 flex items-center w-full p-4 border-b border-border/40">
                <p class="font-semibold text-lg text-text-soft">Filtrar Processos</p>
            </div>

            {{-- BODY --}}
            <div class="flex-1 min-h-0 p-4 overflow-y-auto">

                <div class="grid grid-cols-12 gap-3">

                    {{-- STATUS --}}
                    <div class="relative col-span-12 md:col-span-12 flex flex-col gap-1">
                        <label class="label-input-basic">Status</label>
                        <select x-data="choices($wire.entangle('fields.status'), 'Todos', '', 'auto', true)">
                            <option value="">Todos</option>
                            <option value="draft">Rascunho</option>
                            <option value="awaiting-approval">Aguardando assinaturas</option>
                            <option value="approved">Todos assinaram</option>
                            <option value="failed">Rejeitado</option>
                            <option value="canceled">Cancelado</option>
                        </select>
                    </div>

                    {{-- SIGNERS --}}
                    <div class="relative col-span-12 md:col-span-12 flex flex-col gap-1">
                        <label class="label-input-basic">Signatário</label>
                        <select x-data="choices($wire.entangle('fields.signer'), '---', '', 'auto', true)">
                            @foreach($signers as $user)
                            <option value="{{ $user->ulid }}">{{ $user->display_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- CATEGORIAS --}}
                    <div class="relative col-span-12 md:col-span-12 flex flex-col gap-1">
                        <label class="label-input-basic">Categorias</label>
                        <select x-data="choices($wire.entangle('fields.categories'), '---', '', 'auto', true)" multiple>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="relative col-span-12 md:col-span-12 flex flex-col gap-1">
                        <label class="label-input-basic">Período</label>
                        <input type="text" class="input-basic" x-data="flatpickrPeriod($el, 'fields.period')">
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