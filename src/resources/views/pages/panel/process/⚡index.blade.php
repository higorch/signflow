<?php

use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public function render()
    {
        return $this->view([
            'pageTitle' => $this->pageTitle
        ])->title($this->pageTitle);
    }

    #[Computed]
    public function pageTitle()
    {
        return 'Processos';
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
    </div>
    @endif

    {{-- CABEÇALHO --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-5 mb-3">
        <div class="flex items-center gap-4">
            <h3 class="text-sm md:text-lg font-semibold tracking-wide uppercase text-text-soft">{{ $pageTitle }}</h3>
        </div>
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('panel.processes.create') }}" wire:navigate class="flex-1 md:w-auto inline-flex items-center justify-center gap-1.5 rounded-md bg-primary px-6 py-3 text-xs font-semibold uppercase tracking-wide text-text-soft shadow-lg transition hover:brightness-110">
                <i class="las la-plus text-lg"></i>
                Novo
            </a>
        </div>
    </div>

    <div class="grow flex flex-col">

        <p>...</p>

    </div>

</div>