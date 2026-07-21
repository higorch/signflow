<?php

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination, WithoutUrlPagination;

    public ?string $signerId = null;

    public function render()
    {
        return $this->view([
            'signer' => $this->signer,
            'processSigners' => $this->processSigners,
        ]);
    }

    #[On('opened.modal-singer-processes')]
    public function openModalSignerProcesses($payload)
    {
        $this->signerId = $payload['signerId'] ?? null;
    }

    #[Computed]
    public function signer()
    {
        if (blank($this->signerId)) return null;

        return User::where('ulid', $this->signerId)->first();
    }

    #[Computed]
    public function processSigners()
    {
        if (blank($this->signer)) return collect();

        return $this->signer->processSigners()->with('process')->latest()->paginate(5);
    }
};
?>

<div wire:ignore.self class="fixed inset-0 overflow-y-auto bg-black/60 invisible" x-data="modal('modal-singer-processes')" x-bind="events" :class="{'visible': open, 'invisible': !open}">

    <div class="flex items-center justify-center min-h-dvh p-6" @click.self="open = true">

        <div wire:loading.class="loading-box-fade" class="relative w-full max-w-4xl rounded-md shadow-lg bg-card" x-show="open" x-transition>

            <span class="absolute top-4 right-4 text-lg cursor-pointer text-text-muted hover:text-red-500" @click.prevent="open = false">
                <i class="las la-times"></i>
            </span>

            {{-- HEADER --}}
            <div class="flex items-center gap-1 w-full p-4 border-b border-border/40">
                <p class="font-semibold text-lg text-text-soft">Signatário:</p>
                <span class="font-semibold text-lg text-text-muted/70">{{ $signer->name ?? 'N/A' }}</span>
            </div>

            {{-- BODY --}}
            <div class="flex flex-col grow p-4">

                @if($processSigners->isNotEmpty())
                <div class="col-span-full md:col-span-12 flex flex-col gap-4">

                    {{-- TABELA --}}
                    <div class="overflow-x-auto rounded-md border border-[#2c3446] shadow-xl">
                        <table class="table-primary table-fixed">
                            <thead>
                                <tr>
                                    <th class="sticky left-0">Processo</th>
                                    <th class="w-40">Status</th>
                                    <th>Assina até</th>
                                    <th>Valido até</th>
                                    <th class="sticky right-0 w-40">Ação em</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($processSigners as $signer)
                                <tr wire:key="signer-{{ $signer->id }}">
                                    <td class="sticky left-0 text-xs">
                                        <div class="flex flex-col gap-1">
                                            <span class="text-text-muted/70">{{ $signer->process->reference }}</span>
                                            <span class="text-text-soft">
                                                {{ Str::words($signer->process->title, 3, '...') }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap text-xs w-40">
                                        @php
                                        $badge = match ($signer->status) {
                                        'awaiting-signature' => [
                                        'class' => 'badge-yellow',
                                        'label' => 'Aguardando assinatura',
                                        ],

                                        'signed' => [
                                        'class' => 'badge-green',
                                        'label' => 'Assinado',
                                        ],

                                        'rejected' => [
                                        'class' => 'badge-red',
                                        'label' => 'Rejeitado',
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
                                    <td class="whitespace-nowrap text-xs">{{ $signer->process->sign_deadline_at ? $signer->process->sign_deadline_at->format('d/m/Y H:i:s') : 'N/A' }}</td>
                                    <td class="whitespace-nowrap text-xs">{{ $signer->process->expires_at ? $signer->process->expires_at->format('d/m/Y H:i:s') : 'N/A' }}</td>
                                    <td class="whitespace-nowrap text-xs sticky right-0 w-40">
                                        {{ $signer->action_at ? $signer->action_at->format('d/m/Y H:i:s') : 'N/A' }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($processSigners->hasPages())
                    <div>
                        {{ $processSigners->onEachSide(1)->links('layouts.pagination-simple', data: ['scrollTo' => 'table']) }}
                    </div>
                    @endif

                </div>
                @else
                <div class="col-span-full md:col-span-12 alert alert-info flex items-center justify-between">
                    <div class="flex items-start gap-2">
                        <div class="alert-icon"><i class="las la-info-circle"></i></div>
                        <div class="alert-content leading-normal">Nenhum Signatário.</div>
                    </div>
                </div>
                @endif

            </div>

            {{-- FOOTER --}}
            <div class="flex gap-4 w-full p-4 border-t border-border/40">
                <a href="#" @click.prevent="open = false" class="flex-1 btn-secondary">
                    <i class="las la-times text-lg"></i>Fechar
                </a>
            </div>

        </div>

    </div>

</div>