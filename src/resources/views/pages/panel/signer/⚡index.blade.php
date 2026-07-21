<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public int $perPage = 8;
    public array $search = [];

    public function render()
    {
        return $this->view([
            'pageTitle' => $this->pageTitle,
            'hasSearch' => $this->hasSearch,
            'signers' => $this->signers
        ])->title($this->pageTitle);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    #[On('refresh')]
    public function refresh()
    {
        $this->setPage($this->getPage());
    }

    #[On('set-filter-fields')]
    public function setFilterFields(array $fields)
    {
        $this->search = $fields;
        $this->resetPage();
    }

    #[On('clear-search-singers')]
    public function clearSearchUsers()
    {
        $this->reset('search');
    }

    #[Computed]
    public function pageTitle()
    {
        return 'Signatários';
    }

    #[Computed]
    public function hasSearch()
    {
        return count(array_filter($this->search)) > 0;
    }

    #[Computed]
    public function signers()
    {
        $user = Auth::user();

        return User::whereHas('processSigners.process', function ($query) use ($user) {
            if (hmac_hash('customer') === $user->role_hash) return;
            $query->ownedBy($user->id);
        })->withCount([
            'processSigners',
        ])->when(data_get($this->search, 'email'), function ($query, $term) {
            $query->where('email_hash', 'like', "%" . hmac_hash($term) . "%");
        })->when(data_get($this->search, 'status'), function ($query, $term) {
            $query->where('status', $term);
        })->when(data_get($this->search, 'cpf_cnpj'), function ($query, $term) {
            $query->where('cpf_cnpj_hash', hmac_hash($term, true, true));
        })->orderBy('created_at', 'asc')->paginate($this->perPage);
    }

    public function removeSigner(?string $id)
    {
        try {
            $signer = User::withCount([
                'processSigners' => function ($query) {
                    $query->whereIn('status', ['signed', 'rejected']);
                }
            ])->where('ulid', $id)->first();

            if ($signer->process_signers_count) {
                $this->dispatch('notify', msg: 'Signatário vinculado a um processo e não pode ser removido.', type: 'warning');
            } else {
                $signer->processSigners()->delete();
                $this->dispatch('notify', msg: 'Removido com sucesso.', type: 'success');
            }
        } catch (\Throwable $e) {
            Log::channel('signer')->error('Erro ao remover signatário', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            $this->dispatch('notify', msg: 'Erro ao remover.', type: 'error');
        }
    }
};
?>

@php
$search = json_encode($search, JSON_UNESCAPED_UNICODE);
@endphp

<div class="flex-1 flex flex-col">

    @if (session('success'))
    <div class="alert alert-success flex items-center justify-between mb-3">
        <div class="flex items-start gap-2">
            <div class="alert-icon"><i class="las la-check-circle"></i></div>
            <div class="alert-content leading-normal">{{ session('success') }}</div>
        </div>
    </div>
    @endif

    {{-- CABEÇALHO --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-5 mb-3">
        <div class="flex items-center gap-4">
            <h3 class="text-sm md:text-lg font-semibold tracking-wide uppercase text-text-soft">{{ $pageTitle }}</h3>
        </div>
        <div class="flex items-center justify-between gap-3">
            @if ($hasSearch)
            <a href="#" @click.prevent="$dispatch('clear-search-singers')" title="{{ __('app.clear_filters') }}" class="flex-1 md:w-auto h-full inline-flex items-center justify-center gap-1.5 rounded-md px-6 py-3 border border-primary/80 bg-primary/25 hover:bg-primary/40">
                <i class="las la-times text-lg text-text-muted/70"></i>
            </a>
            @endif
            <a href="#" @click.prevent="$dispatch('open-modal-signer-filter', {fields: {{ $search }}})" title="Filtrar" class="flex-1 md:w-auto h-full inline-flex items-center justify-center gap-1.5 rounded-md px-6 py-3 bg-primary text-text-soft">
                <i class="las la-filter text-lg"></i>
            </a>
        </div>
    </div>

    <div class="grow flex flex-col gap-3">

        @if($signers->isNotEmpty())

        {{-- TABELA --}}
        <div class="overflow-x-auto rounded-md border border-[#2c3446] shadow-xl">
            <table class="table-primary table-fixed">
                <thead>
                    <tr>
                        <th class="sticky left-0">Nome</th>
                        <th>E-mail</th>
                        <th>Processos</th>
                        <th>CPF/CNPJ</th>
                        <th class="w-45">Status</th>
                        <th class="sticky right-0 w-12 text-center"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($signers as $signer)
                    <tr wire:key="signer-{{ $signer->id }}">
                        <td class="sticky left-0">{{ $signer->name }}</td>
                        <td class="whitespace-nowrap text-xs">{{ $signer->email }}</td>
                        <td class="whitespace-nowrap text-xs">
                            <button type="button" @click.prevent="$dispatch('open-modal-singer-processes', {signerId: '{{ $signer->ulid }}' })" class="flex-1 md:w-auto h-full inline-flex items-center justify-center gap-1.5 rounded-md px-3 py-0.5 cursor-pointer border border-primary/80 bg-primary/25 hover:bg-primary/40">
                                ({{ $signer->process_signers_count }})
                                <span>Ver todos</span>
                            </button>
                        </td>
                        <td class="whitespace-nowrap text-xs">{{ $signer->cpf_cnpj ? maskFormat('cpf_cnpj', $signer->cpf_cnpj) : 'N/A' }}</td>
                        <td class="whitespace-nowrap text-xs w-45">
                            @php
                            $badge = match ($signer->status) {
                            'active' => [
                            'class' => 'badge-green',
                            'label' => 'Ativo',
                            ],

                            'disabled' => [
                            'class' => 'badge-red',
                            'label' => 'Inativo',
                            ],

                            default => [
                            'class' => 'badge-black',
                            'label' => $signer->status,
                            ],
                            };
                            @endphp

                            <span class="w-full badge {{ $badge['class'] }}">
                                {{ $badge['label'] }}
                            </span>
                        </td>
                        <td class="sticky right-0 w-12 text-center">
                            <div x-data="dropdown('left-start', 'absolute', 5)" @click.outside="open = false" class="relative z-20">
                                <a x-ref="referenceDropdown" href="#" class="flex items-center justify-center w-9 h-9 text-gray-500 hover:text-text-soft transition" @click.prevent="open = !open">
                                    <i class="las la-ellipsis-v text-lg"></i>
                                </a>
                                <div x-ref="floatingDropdown" :class="{'flex':open,'hidden':!open}" class="absolute right-0 hidden w-40 flex-col gap-1 rounded-md border border-border bg-card p-2 shadow-lg">
                                    <a href="{{ route('panel.signers.edit', ['user' => $signer->ulid]) }}" wire:navigate class="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-text-muted/75 transition hover:bg-card-hover hover:text-text">
                                        <i class="las la-pen"></i>Editar
                                    </a>
                                    <div class="my-1 h-px bg-border"></div>
                                    <a href="#" wire:click.prevent="removeSigner('{{ $signer->ulid }}')" wire:confirm-modal="Excluir Processo | Deseja excluir o signatário '{{ $signer->name }}' permanentemente?" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-text-muted/75 transition hover:bg-card-hover hover:text-text">
                                        <i class="las la-trash"></i>Excluir
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($signers->hasPages())
        <div>
            {{ $signers->onEachSide(1)->links('layouts.pagination', data: ['scrollTo' => 'body']) }}
        </div>
        @endif

        @else

        <div class="col-span-full md:col-span-12 alert alert-info flex items-center justify-between">
            <div class="flex items-start gap-2">
                <div class="alert-icon"><i class="las la-info-circle"></i></div>
                <div class="alert-content leading-normal">Nenhum signatário.</div>
            </div>
        </div>

        @endif

    </div>

    @teleport('body')
    <div>
        <livewire:panel.signer.modal-filter />
        <livewire:panel.signer.modal-processes />
    </div>
    @endteleport

</div>