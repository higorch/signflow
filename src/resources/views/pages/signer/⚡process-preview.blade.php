<?php

use App\Models\Process;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Process $process;

    public function render()
    {
        return $this->view([
            'pageTitle' => $this->pageTitle
        ])->layout('layouts::signer')->title($this->pageTitle);
    }

    public function mount(Process $process)
    {
        abort_if(!$process, 404);

        $this->process = $process->load([
            'signers.user',
            'signers.department',
            'processFiles' => function ($query) {
                $query->orderByRaw('CASE WHEN sort IS NULL THEN 1 ELSE 0 END')->orderBy('sort')->orderBy('created_at');
            },
            'signers' => function ($query) {
                $query->with([
                    'user',
                    'department',
                ])->orderByRaw('CASE WHEN sort IS NULL THEN 1 ELSE 0 END')->orderBy('sort')->orderBy('created_at');
            },
        ]);
    }

    #[Computed]
    public function pageTitle()
    {
        return 'Preview Processo';
    }
};
?>

<div class="flex-1 flex flex-col max-w-2xl w-full mx-auto min-h-dvh">

    {{-- Cabeçalho --}}
    <div class="border-b border-border/40 pb-6">
        <span class="text-sm text-text-muted">REF. {{ $process->reference }}</span>
        <h1 class="mt-1 text-3xl font-semibold tracking-tight text-text">{{ $process->title }}</h1>
        @foreach (preg_split('/\R+/', trim($process->description)) as $paragraph)
        <p class="mt-3 max-w-3xl text-sm leading-6 text-text-muted">
            {{ $paragraph }}
        </p>
        @endforeach
    </div>

    {{-- Arquivos --}}
    <div class="mt-10">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-text">
                Arquivos
            </h2>
            <span class="text-sm text-text-muted">
                {{ $process->processFiles->count() }} mídias(s)
            </span>
        </div>
        <div class="flex flex-col gap-3">
            @forelse($process->processFiles as $file)
            @php
            $isPdf = $file->extension === 'pdf';
            @endphp
            <div class="rounded-md border border-border bg-surface p-4">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-border bg-background">
                            @if($isPdf)
                            <i class="las la-file-pdf text-2xl text-primary"></i>
                            @else
                            <i class="las la-image text-2xl text-primary"></i>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium text-text">
                                {{ $file->caption ?? 'Sem legenda' }}
                            </div>
                            <div class="mt-1 flex items-center gap-2 text-xs text-text-muted">
                                <span>{{ $isPdf ? 'PDF' : 'IMAGEM' }}</span>
                                <span>•</span>
                                <span>{{ maskFormat('file_size', $file->size) }}</span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ $file->signed_url }}" target="_blank" class="inline-flex w-full md:w-auto items-center justify-center gap-1.5 rounded-md border border-primary/80 bg-primary/25 px-3 py-2 text-[10px] uppercase tracking-wide text-text transition hover:bg-primary/40">
                        <i class="las la-external-link-alt text-base"></i>
                        <span>Visualizar</span>
                    </a>
                </div>
            </div>
            @empty
            <div class="rounded-md border border-dashed border-border p-8 text-center text-sm text-text-muted">
               Nenhuma mídia disponível.
            </div>
            @endforelse
        </div>

    </div>

    {{-- Signatários --}}
    <div class="mt-10">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-text">
                Signatários
            </h2>
            <span class="text-sm text-text-muted">
                {{ $process->signers->count() }} participante(s)
            </span>
        </div>
        <div class="flex flex-col gap-3">
            @forelse($process->signers as $signer)
            <div class="rounded-md border border-border bg-surface p-4">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-2x border border-border text-primary bg-background">
                            {{ initials($signer->user->display_name) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium text-text">
                                {{ $signer->user->display_name }}
                            </div>
                            <div class="mt-1 flex items-center gap-2 text-xs text-text-muted">
                                <span>{{ $signer->department ? $signer->department->title : 'Sem departamento' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex w-full md:w-auto items-center justify-center gap-2 rounded-md border border-border bg-background px-3 py-2">
                        @if($signer->status === 'signed')
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        <span class="text-sm text-text">Assinado</span>
                        @elseif($signer->status === 'rejected')
                        <span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>
                        <span class="text-sm text-text">Reprovado</span>
                        @else
                        <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                        <span class="text-sm text-text">Aguardando assinatura</span>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="rounded-md border border-dashed border-border p-8 text-center text-sm text-text-muted">
                Nenhum signatário relacionado.
            </div>
            @endforelse
        </div>

    </div>

    {{-- Footer --}}
    <div class="mt-10 border-t border-border/40 pt-8 space-y-3">
        <div class="flex flex-col items-center gap-3">
            <img src="{{ Vite::asset('resources/assets/images/logo-white.png') }}" alt="Logo" class="h-9 w-auto opacity-90">
            <p class="max-w-sm text-center text-xs leading-5 text-text-muted">
                Plataforma de assinatura eletrônica desenvolvida para garantir autenticidade,
                integridade e rastreabilidade durante todo o fluxo de aprovação dos processos.
            </p>
        </div>
    </div>

    @teleport('body')
    <div>
        <livewire:signer.modal-process-rejected />
    </div>
    @endteleport

</div>