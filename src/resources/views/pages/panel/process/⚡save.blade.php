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
        return 'Cadastrar Processo';
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
        <a href="{{ route('panel.processes.index') }}" wire:navigate class="px-2 py-1 border border-emerald-400/40 rounded text-emerald-300 hover:text-emerald-200 hover:border-emerald-300 transition text-[11px] tracking-wide uppercase">Voltar</a>
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
    <div class="grow">

        <div class="grid grid-cols-12 gap-4 rounded-md p-4 border border-border bg-card shadow-xl">

            {{-- NOME --}}
            <div class="relative col-span-full md:col-span-6 flex flex-col gap-2">
                <label class="label-input-basic">Nome e sobrenome</label>
                <div class="relative">
                    <input type="text" wire:model="form.name" class="input-basic">
                    <x-global.limit-input :limit="40" :model="'form.name'" :stop="true" :align="'center'" />
                </div>
                @error('form.name') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
            </div>

            {{-- DATA NASCIMENTO --}}
            <div class="relative col-span-full md:col-span-2 flex flex-col gap-2">
                <label class="label-input-basic">Data de nascimento</label>
                <input x-data="flatpickrDateBirth($el, 'form.date_birth')" type="text" wire:model="form.date_birth" class="input-basic" placeholder="__/__/____">
                @error('form.date_birth') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
            </div>

            {{-- EMAIL --}}
            <div class="relative col-span-full md:col-span-4 flex flex-col gap-2">
                <label class="label-input-basic">E-mail</label>
                <input type="email" wire:model="form.email" class="input-basic">
                @error('form.email') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
            </div>

            {{-- WHATSAPP --}}
            <div class="relative col-span-full md:col-span-4 flex flex-col gap-2">
                <label class="label-input-basic">WhatsApp</label>
                <input type="text" wire:model="form.whatsapp" class="input-basic" x-data="mask" data-inputmask="'mask': ['(99) 9999-9999', '(99) 99999-9999'], 'keepStatic': true">
                @error('form.whatsapp') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
            </div>

            {{-- CPF/CNPJ --}}
            <div class="relative col-span-full md:col-span-4 flex flex-col gap-2">
                <label class="label-input-basic">CPF / CNPJ</label>
                <input type="text" wire:model="form.cpf_cnpj" class="input-basic" x-data="mask" data-inputmask="'mask': ['999.999.999-99', '99.999.999/9999-99'], 'keepStatic': true">
                @error('form.cpf_cnpj') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
            </div>

            {{-- STATUS --}}
            <div class="relative col-span-full md:col-span-4 flex flex-col gap-2">
                <label class="label-input-basic">Status</label>
                <select x-data="choices($wire.entangle('form.status'), '---', '', 'auto', true)">
                    <option value="active">Ativado</option>
                    <option value="deactive">Desativado</option>
                </select>
                @error('form.status') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
            </div>

        </div>

    </div>

    {{-- AÇÕES --}}
    <div class="flex flex-col-reverse gap-4 md:flex-row md:items-center md:justify-between mt-3">
        <a href="{{ route('panel.processes.index') }}" wire:navigate class="text-center text-[11px] uppercase tracking-wide text-text-soft/50 transition hover:text-text-soft"><i class="las la-angle-left text-xs"></i> Voltar</a>
    </div>

</div>