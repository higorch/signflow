<?php

use Livewire\Component;

new class extends Component
{
    public function delete()
    {
        return;
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
            <h3 class="text-sm md:text-lg font-semibold tracking-wide uppercase text-text-soft">Signatários</h3>
        </div>
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('panel.signers.create') }}" wire:navigate class="flex-1 md:w-auto inline-flex items-center justify-center gap-1.5 rounded-md bg-primary px-6 py-3 text-xs font-semibold uppercase tracking-wide text-text-soft shadow-lg transition hover:brightness-110">
                <i class="las la-plus text-lg"></i>
                Novo
            </a>
        </div>
    </div>

    <div class="grow flex flex-col">

        {{-- TABELA --}}
        <div class="overflow-x-auto rounded-md border border-[#2c3446] shadow-xl">
            <table class="table-primary table-fixed">
                <thead>
                    <tr>
                        <th class="sticky left-0">Nome</th>
                        <th>E-mail</th>
                        <th class="sticky right-0 w-12 text-center"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="sticky left-0">Higor Ferreira</td>
                        <td class="whitespace-nowrap text-xs">higor@mail.com</td>
                        <td class="sticky right-0 w-12 text-center">
                            <div x-data="dropdown('left-start', 'absolute', 5)" @click.outside="open = false" class="relative z-20">
                                <a x-ref="referenceDropdown" href="#" class="flex items-center justify-center w-9 h-9 text-gray-500 hover:text-text-soft transition" @click.prevent="open = !open">
                                    <i class="las la-ellipsis-v text-lg"></i>
                                </a>
                                <div x-ref="floatingDropdown" :class="{'flex':open,'hidden':!open}" class="absolute right-0 hidden w-40 flex-col gap-1 rounded-md border border-border bg-card p-2 shadow-lg">
                                    <a href="#" wire:navigate class="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-text-muted/75 transition hover:bg-card-hover hover:text-text">
                                        <i class="las la-pen"></i>Editar
                                    </a>
                                    <div class="my-1 h-px bg-border"></div>
                                    <a href="#" wire:click.prevent="delete" wire:confirm-modal="Excluir Usuário | Deseja realmente excluir este usuário permanentemente?" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-text-muted/75 transition hover:bg-card-hover hover:text-text">
                                        <i class="las la-trash"></i>Excluir
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

</div>