<?php

use Carbon\Carbon;
use App\Models\Process;
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
            'processes' => $this->processes,
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

    #[On('clear-search-processes')]
    public function clearSearchProcesses()
    {
        $this->reset('search');
    }

    #[Computed]
    public function pageTitle()
    {
        return 'Processos';
    }

    #[Computed]
    public function hasSearch()
    {
        return count(array_filter($this->search)) > 0;
    }

    #[Computed]
    public function processes()
    {
        $user = Auth::user();
        $query = Process::query();

        if ($user->role_hash === hmac_hash('customer')) {
            $ownerIds = $user->internalUsers()->pluck('users.id')->push($user->id);
            $query->whereIn('owner_id', $ownerIds);
        } else {
            $query->ownedBy($user->id);
        }

        return $query->with('category')->when(data_get($this->search, 'status'), function ($query, $status) {
            $query->where('status', $status);
        })->when(data_get($this->search, 'signer'), function ($query, $signer) {
            $query->whereHas('signers.user', function ($query) use ($signer) {
                $query->where('ulid', $signer);
            });
        })->when(data_get($this->search, 'categories'), function ($query, $categories) {
            $query->whereIn('category_id', $categories);
        })->when(data_get($this->search, 'period'), function ($query, $period) {
            if (empty($period['from']) || empty($period['to'])) {
                return;
            }

            $query->whereBetween('created_at', [
                Carbon::createFromFormat('d/m/Y', $period['from'])->startOfDay(),
                Carbon::createFromFormat('d/m/Y', $period['to'])->endOfDay(),
            ]);
        })->latest()->paginate($this->perPage);
    }

    public function removeProcess(?string $id): void
    {
        try {
            $process = Process::withCount([
                'signers' => function ($query) {
                    $query->whereIn('status', ['signed', 'rejected']);
                }
            ])->findOrFail($id);

            $this->authorize('delete', $process);

            if ($process->signers_count > 0) {
                $this->dispatch('notify', msg: 'O processo já possui assinaturas e não pode ser removido.', type: 'warning');
                return;
            }

            $process->delete();

            $this->dispatch('notify', msg: 'Processo removido com sucesso.', type: 'success');
        } catch (\Throwable $e) {
            Log::channel('process')->error('Erro ao remover processo', [
                'process_id' => $id,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            $this->dispatch('notify', msg: 'Erro ao remover o processo.', type: 'error');
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
            <a href="#" @click.prevent="$dispatch('clear-search-processes')" title="{{ __('app.clear_filters') }}" class="flex-1 md:w-auto h-full inline-flex items-center justify-center gap-1.5 rounded-md px-6 py-3 border border-primary/80 bg-primary/25 hover:bg-primary/40">
                <i class="las la-times text-lg text-text-muted/70"></i>
            </a>
            @endif
            <a href="#" @click.prevent="$dispatch('open-modal-process-filter', {fields: {{ $search }}})" title="Filtrar" class="flex-1 md:w-auto h-full inline-flex items-center justify-center gap-1.5 rounded-md px-6 py-3 bg-primary text-text-soft">
                <i class="las la-filter text-lg"></i>
            </a>
            <a href="#" @click.prevent="$dispatch('open-modal-process-create')" class="flex-1 md:w-auto h-full inline-flex items-center justify-center gap-1.5 rounded-md px-6 py-3 bg-primary text-text-soft">
                <i class="las la-plus text-lg"></i>
            </a>
        </div>
    </div>

    <div class="grow flex flex-col gap-3">

        @if($processes->isNotEmpty())

        {{-- TABELA --}}
        <div class="overflow-x-auto rounded-md border border-[#2c3446] shadow-xl">
            <table class="table-primary table-fixed">
                <thead>
                    <tr>
                        <th class="sticky left-0">Ref.</th>
                        <th>Título</th>
                        <th class="w-45">Status</th>
                        <th>Categoria</th>
                        <th>Assina até</th>
                        <th>Valido até</th>
                        <th>Criaddo em</th>
                        <th class="w-8 text-center"></th>
                        <th class="sticky right-0 w-12 text-center"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($processes as $process)
                    <tr wire:key="process-{{ $process->id }}">
                        <td class="sticky left-0">{{ $process->reference }}</td>
                        <td class="whitespace-nowrap text-xs">{{ Str::words($process->title, 5, '...') }}</td>
                        <td class="whitespace-nowrap text-xs w-45">
                            @php
                            $badge = match ($process->status) {
                            'draft' => [
                            'class' => 'badge-gray',
                            'label' => 'Rascunho',
                            ],

                            'awaiting-approval' => [
                            'class' => 'badge-yellow',
                            'label' => 'Aguardando assinaturas',
                            ],

                            'approved' => [
                            'class' => 'badge-green',
                            'label' => 'Todos assinaram',
                            ],

                            'failed' => [
                            'class' => 'badge-red',
                            'label' => 'Rejeitado',
                            ],

                            'canceled' => [
                            'class' => 'badge-red',
                            'label' => 'Cancelado',
                            ],

                            default => [
                            'class' => 'badge-black',
                            'label' => 'N/A',
                            ],
                            };
                            @endphp

                            <span class="w-full badge {{ $badge['class'] }}">
                                {{ $badge['label'] }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap text-xs">{{ $process->category ? $process->category->title : 'N/A' }}</td>
                        <td class="whitespace-nowrap text-xs">{{ $process->sign_deadline_at ? $process->sign_deadline_at->format('d/m/Y H:i:s') : 'N/A' }}</td>
                        <td class="whitespace-nowrap text-xs">{{ $process->expires_at ? $process->expires_at->format('d/m/Y H:i:s') : 'N/A' }}</td>
                        <td class="whitespace-nowrap text-xs">{{ $process->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="whitespace-nowrap text-xs w-8">
                            <a href="{{ route('signer.process-preview', ['process' => $process->id ]) }}" target="_blank" class="flex-1 md:w-auto h-full inline-flex items-center justify-center gap-1.5 rounded-md px-3 py-0.5 cursor-pointer border border-primary/80 bg-primary/25 hover:bg-primary/40">
                                <span>Visualizar</span>
                            </a>
                        </td>
                        <td class="sticky right-0 w-12 text-center">
                            <div x-data="dropdown('left-start', 'absolute', 5)" @click.outside="open = false" class="relative z-20">
                                <a x-ref="referenceDropdown" href="#" class="flex items-center justify-center w-9 h-9 text-gray-500 hover:text-text-soft transition" @click.prevent="open = !open">
                                    <i class="las la-ellipsis-v text-lg"></i>
                                </a>
                                <div x-ref="floatingDropdown" :class="{'flex':open,'hidden':!open}" class="absolute right-0 hidden w-40 flex-col gap-1 rounded-md border border-border bg-card p-2 shadow-lg">
                                    <a href="{{ route('panel.processes.edit', ['process' => $process->id]) }}" wire:navigate class="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-text-muted/75 transition hover:bg-card-hover hover:text-text">
                                        <i class="las la-pen"></i>Editar
                                    </a>
                                    <div class="my-1 h-px bg-border"></div>
                                    <a href="#" wire:click.prevent="removeProcess('{{ $process->id }}')" wire:confirm-modal="Excluir Processo | Deseja excluir o processo '{{ $process->reference }}' permanentemente?" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-text-muted/75 transition hover:bg-card-hover hover:text-text">
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

        @if($processes->hasPages())
        <div>
            {{ $processes->onEachSide(1)->links('layouts.pagination', data: ['scrollTo' => 'body']) }}
        </div>
        @endif

        @else

        @if($hasSearch)
        <div class="col-span-full md:col-span-12 alert alert-info flex items-center justify-between">
            <div class="flex items-start gap-2">
                <div class="alert-icon"><i class="las la-info-circle"></i></div>
                <div class="alert-content leading-normal">Nenhum processo para o filtro aplicado.</div>
            </div>
        </div>
        @else
        <div class="flex-1 flex flex-col">

            <div class="grow flex flex-col justify-center gap-4 md:gap-5 rounded-md border border-border/50 bg-card-hover/50 p-4 backdrop-blur-sm">
                <div class="flex flex-col gap-3 items-center justify-center">
                    <div class="flex items-center justify-center size-8 md:size-10 rounded-full border border-primary/20 bg-primary/10">
                        <i class="las la-file-signature text-xl md:text-2xl text-primary"></i>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <p class="text-center text-sm font-medium text-text">
                            Nenhum processo cadastrado.
                        </p>
                    </div>
                </div>
                <a href="#" @click.prevent="$dispatch('open-modal-process-create')" class="inline-flex w-fit self-center items-center justify-center gap-1.5 rounded-md border border-primary/80 bg-primary/25 px-3 py-2 text-[10px] uppercase tracking-wide text-text transition hover:bg-primary/40">
                    <i class="las la-plus text-[15px] text-text-muted-[#ffcf93]/70"></i>
                    Adicione o primeiro
                </a>
            </div>

        </div>
        @endif

        @endif

    </div>

    @teleport('body')
    <div>
        <livewire:panel.process.modal-create />
        <livewire:panel.process.modal-filter />
    </div>
    @endteleport

</div>