<?php

use App\Models\Process;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public Process $process;

    public array $form = [
        'category' => '',
        'title' => '',
        'description' => '',
    ];

    public function render()
    {
        return $this->view([
            'pageTitle' => $this->pageTitle
        ])->title($this->pageTitle);
    }

    #[On('refresh')]
    public function refresh() {}

    public function mount(Process $process)
    {
        $this->process = $process->load([
            'owner',
            'category',
            'events',
            'signers',
            'processFiles',
        ]);

        $this->form = [
            'category' => $process->category_id,
            'title' => $process->title,
            'description' => $process->description,
        ];
    }

    #[Computed]
    public function pageTitle()
    {
        return 'Editar Processo';
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
            <a href="{{ route('panel.processes.index') }}" wire:navigate class="inline-flex items-center justify-center rounded-md border border-[#394150]/30 bg-[#394150]/5 px-3 py-2 text-text-soft transition hover:bg-surface-hover">
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
    <div class="flex flex-col grow gap-4">

        <div class="col-span-12 md:col-span-12 grid grid-cols-12 gap-4 rounded-md p-4 border border-border bg-card shadow-xl">

            {{-- TITLE --}}
            <div class="relative col-span-full md:col-span-12 flex flex-col gap-2">
                <label class="label-input-basic">Título</label>
                <div class="relative">
                    <input type="text" wire:model="form.title" class="input-basic">
                    <x-global.limit-input :limit="120" :model="'form.title'" :stop="true" :align="'center'" />
                </div>
                @error('form.title') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
            </div>

            {{-- DESCRIPTION --}}
            <div class="relative col-span-full md:col-span-12 flex flex-col gap-2">
                <label class="label-input-basic">Descrição</label>
                <div class="relative">
                    <textarea wire:model="form.description" class="input-basic min-h-30 resize-none"></textarea>
                    @error('form.description') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
                </div>
            </div>

        </div>

        <div class="col-span-12 md:col-span-12 grid grid-cols-12 gap-4 rounded-md p-4 border border-border bg-card shadow-xl">

            <div class="col-span-full md:col-span-12 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <h3 class="font-semibold text-xs uppercase tracking-wide text-text-muted/80">
                    Arquivos(s)
                </h3>
                <div class="flex items-center gap-3">
                    <a href="#" @click.prevent="$dispatch('open-modal-files-upload', { processId: '{{ $process->id }}' })" class="inline-flex max-md:flex-1 items-center justify-center gap-1.5 rounded-md border border-primary/80 bg-primary/25 px-3 py-2 text-[10px] uppercase tracking-wide text-text transition hover:bg-primary/40">
                        <i class="las la-plus text-[15px] text-text-muted-[#ffcf93]/70"></i>
                        Adicionar
                    </a>
                </div>

            </div>

            <div class="col-span-full md:col-span-12 alert alert-info flex items-center justify-between">
                <div class="flex items-start gap-2">
                    <div class="alert-icon"><i class="las la-info-circle"></i></div>
                    <div class="alert-content leading-normal">Nenhum Arquivo.</div>
                </div>
            </div>

        </div>

        <div class="col-span-12 md:col-span-12 grid grid-cols-12 gap-4 rounded-md p-4 border border-border bg-card shadow-xl">

            <div class="col-span-full md:col-span-12 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <h3 class="font-semibold text-xs uppercase tracking-wide text-text-muted/80">
                    Signatário(s)
                </h3>
                <div class="flex items-center gap-3">
                    <a href="#" @click.prevent="$dispatch('open-modal-signer', { processId: '{{ $process->id }}' })" class="inline-flex max-md:flex-1 items-center justify-center gap-1.5 rounded-md border border-primary/80 bg-primary/25 px-3 py-2 text-[10px] uppercase tracking-wide text-text transition hover:bg-primary/40">
                        <i class="las la-plus text-[15px] text-text-muted-[#ffcf93]/70"></i>
                        Adicionar
                    </a>
                </div>
            </div>

            <div class="col-span-full md:col-span-12 alert alert-info flex items-center justify-between">
                <div class="flex items-start gap-2">
                    <div class="alert-icon"><i class="las la-info-circle"></i></div>
                    <div class="alert-content leading-normal">Nenhum Signatário.</div>
                </div>
            </div>

        </div>

        <div class="col-span-12 md:col-span-12 grid grid-cols-12 gap-4 rounded-md p-4 border border-border bg-card shadow-xl">

            <div class="col-span-full md:col-span-12 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <h3 class="font-semibold text-xs uppercase tracking-wide text-text-muted/80">
                    Histórico
                </h3>
            </div>

            <div class="col-span-full md:col-span-12 alert alert-info flex items-center justify-between">
                <div class="flex items-start gap-2">
                    <div class="alert-icon"><i class="las la-info-circle"></i></div>
                    <div class="alert-content leading-normal">Nenhum histórico.</div>
                </div>
            </div>

        </div>

    </div>

    {{-- AÇÕES --}}
    <div class="flex flex-col-reverse gap-4 md:flex-row md:items-center md:justify-between mt-3">
        <a href="{{ route('panel.processes.index') }}" wire:navigate class="text-center text-[11px] uppercase tracking-wide text-text-soft/50 transition hover:text-text-soft"><i class="las la-angle-left text-xs"></i> Voltar</a>
    </div>

    @teleport('body')
    <div>
        <livewire:panel.process.modal-files-upload />
        <livewire:panel.process.modal-signer />
    </div>
    @endteleport

</div>