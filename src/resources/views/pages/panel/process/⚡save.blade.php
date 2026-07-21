<?php

use App\Models\Attachment;
use App\Models\Category;
use App\Models\Process;
use App\Models\ProcessSigner;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public Process $process;

    public array $captions = [];

    public array $form = [
        'category' => '',
        'title' => '',
        'description' => '',
    ];

    public function render()
    {
        return $this->view([
            'pageTitle' => $this->pageTitle,
            'categories' => $this->categories,
            'processFiles' => $this->processFiles,
            'signers' => $this->signers
        ])->title($this->pageTitle);
    }

    public function exception($e, $stopPropagation)
    {
        if ($e instanceof ValidationException) {
            $this->dispatch('errors-process-save', errors: $this->getErrorBag());
            $this->errorToastErrorBag();
        }
    }

    #[On('refresh')]
    public function refresh() {}

    public function mount(Process $process)
    {
        $this->process = $process->load([
            'owner',
            'category',
            'events'
        ]);

        $this->form = [
            'category' => $process->category_id,
            'title' => $process->title,
            'description' => $process->description,
        ];

        $this->captions = $this->process->processFiles()->pluck('caption', 'id')->toArray();
    }

    #[On('sort-process-files')]
    public function reorderFiles(?array $ids)
    {
        collect($ids)->unique()->values()->each(function ($id, $index) {
            $this->process->processFiles()->where('id', $id)->update([
                'sort' => $index
            ]);
        });
    }

    #[On('sort-process-signers')]
    public function reorderSigners(?array $ids)
    {
        collect($ids)->unique()->values()->each(function ($id, $index) {
            $this->process->signers()->where('id', $id)->update([
                'sort' => $index
            ]);
        });
    }

    #[Computed]
    public function pageTitle()
    {
        return 'Editar Processo';
    }

    #[Computed]
    public function categories()
    {
        return Category::where('taxonomy', 'process')->get();
    }

    #[Computed]
    public function processFiles()
    {
        if (blank(data_get($this->process, 'processFiles'))) return collect();

        return $this->process
            ->processFiles()
            ->orderByRaw('CASE WHEN sort IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sort')
            ->orderBy('created_at')
            ->get();
    }

    #[Computed]
    public function signers()
    {
        if (blank(data_get($this->process, 'signers'))) return collect();

        return $this->process
            ->signers()
            ->with([
                'user',
                'department'
            ])
            ->orderByRaw('CASE WHEN sort IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sort')
            ->orderBy('created_at')
            ->get();
    }

    public function submit(string $processStatus)
    {
        $this->validate();

        DB::beginTransaction();

        try {
            if (!in_array($processStatus, ['draft', 'awaiting-approval'])) {
                throw new \Exception('Status inválido', 422);
            }

            $this->process->update([
                'category_id' => $this->form['category'],
                'title' => $this->form['title'],
                'description' => $this->form['description'],
                'status' => $processStatus,
            ]);

            foreach ($this->captions as $id => $caption) {
                $this->process->processFiles()->whereKey($id)->update([
                    'caption' => trim($caption)
                ]);
            }

            if ($processStatus === 'awaiting-approval') {
                dispatch(new \App\Jobs\SendProcessEmailToSignerJob($this->process->id))->afterCommit();
                session()->flash('success', 'Enviado, em breve os signatários vão receber o e-mail.');
            } else {
                session()->flash('success', 'Processo salvo com sucesso.');
            }

            DB::commit();

            return $this->redirectRoute('panel.processes.edit', [
                'process' => $this->process->id,
            ], navigate: true);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::channel('process')->error('Erro ao salvar', [
                'process_id' => $this->process->id,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            match ($e->getCode()) {
                422 => $this->dispatch('notify', msg: $e->getMessage(), type: 'error'),
                default => $this->dispatch('notify', msg: 'Não foi possível salvar.', type: 'error'),
            };
        }
    }

    public function draft()
    {
        DB::beginTransaction();

        try {
            $this->process->update([
                'category_id' => $this->form['category'],
                'title' => $this->form['title'],
                'description' => $this->form['description'],
                'status' => 'draft',
            ]);

            foreach ($this->captions as $id => $caption) {
                $this->process->processFiles()->whereKey($id)->update([
                    'caption' => trim($caption)
                ]);
            }

            dispatch(new \App\Jobs\SendProcessReturnedToDraftEmailToSignerJob($this->process->id))->afterCommit();

            DB::commit();

            session()->flash('success', 'Processo retornado para rascunho com sucesso.');

            return $this->redirectRoute('panel.processes.edit', [
                'process' => $this->process->id,
            ], navigate: true);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::channel('process')->error('Erro ao retornar processo para rascunho', [
                'process_id' => $this->process->id,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            $this->dispatch('notify', msg: 'Não foi possível retornar o processo para rascunho.', type: 'error');
        }
    }

    public function removeFile(?string $id)
    {
        try {
            $attachment = Attachment::findOrFail($id);

            if (Storage::disk($attachment->disk)->exists($attachment->path)) {
                Storage::disk($attachment->disk)->delete($attachment->path);
            }

            $attachment->delete();

            $this->dispatch('notify', msg: 'Removido com sucesso.', type: 'success');
        } catch (\Throwable $e) {
            Log::channel('process')->error('Erro ao remover arquivo', [
                'attachment_id' => $id,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            $this->dispatch('notify', msg: 'Erro ao remover.', type: 'error');
        }
    }

    public function removeSigner(?string $id)
    {
        try {
            $processSigner = ProcessSigner::findOrFail($id);

            (match ($processSigner->status) {
                'signed' => function () {
                    $this->dispatch('notify', msg: 'Não é possível remover um assinante que já assinou o documento.', type: 'warning');
                },
                'rejected' => function () {
                    $this->dispatch('notify', msg: 'Não é possível remover um assinante que já rejeitou o documento.', type: 'warning');
                },
                default => function () use ($processSigner) {
                    $processSigner->delete();
                    $this->dispatch('notify', msg: 'Removido com sucesso.', type: 'success');
                },
            })();
        } catch (\Throwable $e) {
            Log::channel('process')->error('Erro ao remover signatário', [
                'attachment_id' => $id,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            $this->dispatch('notify', msg: 'Erro ao remover.', type: 'error');
        }
    }

    protected function prepareForValidation($attributes): array
    {
        return $attributes;
    }

    protected function rules()
    {
        return [
            'captions.*' => [
                'required',
                'max:40',
            ],
            'form.category' => [
                'required',
                Rule::exists('categories', 'id')->where('taxonomy', 'process'),
            ],
            'form.title' => [
                'min:5',
                'max:120',
                'required',
                function ($attribute, $value, $fail) {
                    $value = trim($value);

                    if (preg_match('/[\x{1F300}-\x{1FAFF}]/u', $value)) {
                        $fail(__('validation.sem_emoji'));
                        return;
                    }

                    $parts = preg_split('/\s+/', $value);

                    foreach ($parts as $part) {
                        if (! preg_match('/^[A-Za-zÀ-ÿ]+$/u', $part)) {
                            $fail(__('validation.sem_caracteres'));
                            return;
                        }
                    }
                }
            ],
            'form.description' => [
                'min:10',
                'nullable',
                function ($attribute, $value, $fail) {
                    $value = trim($value);

                    if (preg_match('/[\x{1F300}-\x{1FAFF}]/u', $value)) {
                        $fail(__('validation.sem_emoji'));
                        return;
                    }
                }
            ]
        ];
    }

    private function errorToastErrorBag()
    {
        $errors = $this->getErrorBag();
        $count = count($errors->getMessages());

        if ($count > 0) {
            $this->dispatch('notify', msg: $count === 1 ? __('app.one_filling_problem') : $count . ' ' . __('app.filling_problems'), type: 'error');
        }
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
            <h3 class="text-sm md:text-lg font-semibold tracking-wide uppercase">
                <span class="text-text-soft">{{ $pageTitle }}:</span>
                <span class="text-text-muted/70">{{ $process->reference }}</span>
            </h3>
        </div>
        <div class="flex items-center justify-between gap-3">
            @if($process->status === 'awaiting-approval')
            <a href="#" wire:click.prevent="draft" wire:confirm-modal="Retornar para rascunho | O processo voltará para rascunho, ficará indisponível para assinatura e todos os signatários serão notificados por e-mail. Deseja continuar?" class="flex-1 md:w-auto inline-flex items-center justify-center gap-1.5 rounded-md bg-warning px-6 py-3 text-xs md:whitespace-nowrap font-semibold uppercase tracking-wide text-card shadow-lg transition hover:brightness-110">
                <i class="las la-undo-alt text-lg"></i>
                Retornar para rascunho
            </a>
            @else
            <a href="#" wire:click.prevent="submit('awaiting-approval')" wire:confirm-modal="Enviar para assinatura | Deseja enviar o processo para assinatura agora, os signatário serão notificados por e-mail?" class="flex-1 md:w-auto inline-flex items-center justify-center gap-1.5 rounded-md bg-primary px-6 py-3 text-xs md:whitespace-nowrap font-semibold uppercase tracking-wide text-text-soft shadow-lg transition hover:brightness-110">
                <i class="lab la-telegram-plane text-lg"></i>
                Enviar para assinatura
            </a>
            <a href="#" wire:click.prevent="submit('draft')" class="flex-1 md:w-auto inline-flex items-center justify-center gap-1.5 rounded-md bg-primary/25 px-6 py-3 text-xs md:whitespace-nowrap font-semibold uppercase tracking-wide text-text-soft shadow-lg transition hover:brightness-110">
                <i class="las la-save text-lg"></i>
                Salvar Rescunho
            </a>
            @endif
        </div>
    </div>

    {{-- BODY --}}
    <div class="flex flex-col grow gap-4">

        {{-- FORMULÁRIO --}}
        <div class="col-span-12 md:col-span-12 grid grid-cols-12 gap-3 rounded-md p-4 border border-border bg-card shadow-xl">

            <div class="col-span-full md:col-span-12 flex flex-col md:flex-row md:items-center md:justify-between gap-3 pb-3 border-b border-border/25">
                <h3 class="font-semibold text-xs uppercase tracking-wide text-text-muted/80" data-alpine-devtools-right-click="">
                    Dados gerais
                </h3>
            </div>

            {{-- CATEGORY --}}
            <div class="relative col-span-full md:col-span-12 flex flex-col gap-1">
                <label class="label-input-basic">Categoria</label>
                <select x-data="choices($wire.entangle('form.category'), '---', '', 'auto', false)">
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->title }}</option>
                    @endforeach
                </select>
                @error('form.category') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
            </div>

            {{-- TITLE --}}
            <div class="relative col-span-full md:col-span-12 flex flex-col gap-1">
                <label class="label-input-basic">Título</label>
                <div class="relative">
                    <input type="text" wire:model="form.title" class="input-basic">
                    <x-global.limit-input :limit="120" :model="'form.title'" :stop="true" :align="'center'" />
                </div>
                @error('form.title') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
            </div>

            {{-- DESCRIPTION --}}
            <div class="relative col-span-full md:col-span-12 flex flex-col gap-1">
                <label class="label-input-basic">Descrição</label>
                <div class="relative">
                    <textarea wire:model="form.description" class="input-basic min-h-30 resize-none"></textarea>
                    @error('form.description') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full h-full">{{ $message }}</span> @enderror
                </div>
            </div>

        </div>

        {{-- FILES --}}
        <div class="col-span-12 md:col-span-12 grid grid-cols-12 gap-3 rounded-md p-4 border border-border bg-card shadow-xl">

            <div class="col-span-full md:col-span-12 flex flex-col md:flex-row md:items-center md:justify-between gap-3 pb-3 border-b border-border/25">
                <h3 class="font-semibold text-xs uppercase tracking-wide text-text-muted/80">
                    Arquivos(s)
                </h3>
                <div class="flex items-center gap-3">
                    <a href="#" @click.prevent="$dispatch('open-modal-process-files-upload', { processId: '{{ $process->id }}' })" class="inline-flex max-md:flex-1 items-center justify-center gap-1.5 rounded-md border border-primary/80 bg-primary/25 px-3 py-2 text-[10px] uppercase tracking-wide text-text transition hover:bg-primary/40">
                        <i class="las la-plus text-[15px] text-text-muted-[#ffcf93]/70"></i>
                        Adicionar
                    </a>
                </div>

            </div>

            @if($processFiles->isNotEmpty())
            <div x-data="sortable('process-files')" class="col-span-full md:col-span-12 flex flex-col gap-4">

                @foreach($processFiles as $file)

                @php
                $isPdf = $file->extension === 'pdf';
                @endphp

                <div data-sortable-item="{{ $file->id }}" wire:key="file-{{ $file->id }}" wire:loading.class="loading-box-fade" wire:target="removeFile('{{ $file->id }}')" class="group flex flex-col justify-between gap-3 p-3 rounded-md border border-border/25 bg-card">

                    <div class="flex justify-between gap-3">

                        <a href="{{ $file->signed_url }}" target="_blank" rel="noopener noreferrer" class="group relative flex h-18 w-26 items-center justify-center overflow-hidden rounded-md border border-border/85 bg-surface-active transition-all duration-200 group-hover:border-primary group-hover:shadow-md md:h-20 md:w-32">
                            @if($isPdf)
                            <div class="flex h-full w-full flex-col items-center justify-center gap-1">
                                <i class="las la-file-pdf text-4xl text-red-500"></i>
                                <span class="text-[10px] font-medium text-text">PDF</span>
                            </div>
                            @else
                            <img src="{{ $file->signed_url }}" class="h-full w-full object-cover">
                            @endif
                            <div class="absolute top-1.5 right-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-black/70 text-white">
                                <i class="las la-external-link-alt text-xs"></i>
                            </div>
                        </a>

                        <div class="flex-1 flex flex-col justify-between gap-3">

                            <div class="flex items-center justify-between gap-3">
                                <span class="flex items-center gap-1">
                                    @switch($file->extension)
                                    @case('pdf')
                                    <i class="las la-file-alt text-sm text-red-500"></i>
                                    <span class="text-xs text-text">PDF</span>
                                    @break

                                    @default
                                    <i class="las la-image text-sm text-text-muted"></i>
                                    <span class="text-xs text-text">Imagem</span>
                                    @endswitch
                                </span>
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <span class="text-xs text-text-muted">{{ maskFormat('file_size', $file->size) }}</span>
                                <div class="flex items-center gap-3">
                                    <button type="button" @click.prevent data-sortable-handle title="Arrastar" class="inline-flex items-center justify-center rounded-md border border-primary/80 bg-primary/25 px-2 py-1 text-[10px] uppercase tracking-wide text-text transition hover:bg-primary/40">
                                        <i class="las la-arrows-alt text-base"></i>
                                    </button>
                                    <a href="#" wire:click.prevent="removeFile('{{ $file->id }}')" wire:confirm-modal="Excluir arquivo | Deseja realmente excluir o arquivo permanentemente?" title="Remover" class="inline-flex items-center justify-center rounded-md border border-primary/80 bg-primary/25 px-2 py-1 text-[10px] uppercase tracking-wide text-text transition hover:bg-primary/40">
                                        <i class="las la-times text-base"></i>
                                    </a>
                                </div>
                            </div>

                        </div>

                    </div>

                    {{-- CAPTION --}}
                    <div class="relative flex flex-col gap-1">
                        <label class="label-input-basic">Legenda</label>
                        <div class="relative">
                            <input type="text" wire:model="captions.{{ $file->id }}" class="input-basic">
                            <x-global.limit-input :limit="40" :model="'captions.{{ $file->id }}'" :stop="true" :align="'center'" />
                        </div>
                        @error('captions.' . $file->id) <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
                    </div>

                </div>

                @endforeach

            </div>
            @else
            <div class="col-span-full md:col-span-12 alert alert-info flex items-center justify-between">
                <div class="flex items-start gap-2">
                    <div class="alert-icon"><i class="las la-info-circle"></i></div>
                    <div class="alert-content leading-normal">Nenhum Arquivo.</div>
                </div>
            </div>
            @endif

        </div>

        {{-- SIGNERS --}}
        <div class="col-span-12 md:col-span-12 grid grid-cols-12 gap-3 rounded-md p-4 border border-border bg-card shadow-xl">

            <div class="col-span-full md:col-span-12 flex flex-col md:flex-row md:items-center md:justify-between gap-3 pb-3 border-b border-border/25">
                <h3 class="font-semibold text-xs uppercase tracking-wide text-text-muted/80">
                    Signatário(s)
                </h3>
                <div class="flex items-center gap-3">
                    <a href="#" @click.prevent="$dispatch('open-modal-process-signer', { processId: '{{ $process->id }}' })" class="inline-flex max-md:flex-1 items-center justify-center gap-1.5 rounded-md border border-primary/80 bg-primary/25 px-3 py-2 text-[10px] uppercase tracking-wide text-text transition hover:bg-primary/40">
                        <i class="las la-plus text-[15px] text-text-muted-[#ffcf93]/70"></i>
                        Adicionar
                    </a>
                </div>
            </div>

            @if($signers->isNotEmpty())
            <div class="col-span-full md:col-span-12 flex flex-col gap-4">

                {{-- TABELA --}}
                <div class="overflow-x-auto rounded-md border border-[#2c3446] shadow-xl">
                    <table class="table-primary table-fixed">
                        <thead>
                            <tr>
                                <th class="sticky left-0">Nome</th>
                                <th>E-mail</th>
                                <th>Departamento</th>
                                <th>CPF/CNPJ</th>
                                <th class="w-50">Status</th>
                                <th class="w-35">Ação em</th>
                                <th class="sticky right-0 w-30 text-center"></th>
                            </tr>
                        </thead>
                        <tbody x-data="sortable('process-signers')">
                            @foreach($signers as $signer)
                            <tr data-sortable-item="{{ $signer->id }}" wire:key="signer-{{ $signer->id }}">
                                <td class="sticky left-0">{{ $signer->user->name }}</td>
                                <td class="whitespace-nowrap text-xs">{{ $signer->user->email }}</td>
                                <td class="whitespace-nowrap text-xs">
                                    {{ $signer->department ? $signer->department->title : 'N/A' }}
                                </td>
                                <td class="whitespace-nowrap text-xs">
                                    {{ $signer->user->cpf_cnpj ? maskFormat('cpf_cnpj', $signer->user->cpf_cnpj) : 'N/A' }}
                                </td>
                                <td class="whitespace-nowrap text-xs w-50">
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
                                <td class="whitespace-nowrap text-xs w-35">
                                    {{ $signer->action_at ? $signer->action_at->format('d/m/Y H:i:s') : 'N/A' }}
                                </td>
                                <td class="sticky right-0 w-30 text-center">
                                    <div class="flex justify-end items-center gap-3">
                                        <button type="button" @click.prevent data-sortable-handle title="Arrastar" class="inline-flex items-center justify-center rounded-md border border-primary/80 bg-primary/25 px-2 py-1 text-[10px] uppercase tracking-wide text-text transition hover:bg-primary/40">
                                            <i class="las la-arrows-alt text-base"></i>
                                        </button>
                                        <a href="#" wire:click.prevent="removeSigner('{{ $signer->id }}')" wire:confirm-modal="Excluir Signatário | Deseja realmente excluir o signatário '{{ $signer->user->name }}' permanentemente?" title="Remover" class="inline-flex items-center justify-center rounded-md border border-primary/80 bg-primary/25 px-2 py-1 text-[10px] uppercase tracking-wide text-text transition hover:bg-primary/40">
                                            <i class="las la-times text-base"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

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

        {{-- HISTORY --}}
        <div class="col-span-12 md:col-span-12 grid grid-cols-12 gap-3 rounded-md p-4 border border-border bg-card shadow-xl">

            <div class="col-span-full md:col-span-12 flex flex-col md:flex-row md:items-center md:justify-between gap-3 pb-3 border-b border-border/25">
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
        <div class="flex items-center justify-between gap-3">
            @if($process->status === 'awaiting-approval')
            <a href="#" wire:click.prevent="draft" wire:confirm-modal="Retornar para rascunho | O processo voltará para rascunho, ficará indisponível para assinatura e todos os signatários serão notificados por e-mail. Deseja continuar?" class="flex-1 md:w-auto inline-flex items-center justify-center gap-1.5 rounded-md bg-warning px-6 py-3 text-xs md:whitespace-nowrap font-semibold uppercase tracking-wide text-card shadow-lg transition hover:brightness-110">
                <i class="las la-undo-alt text-lg"></i>
                Retornar para rascunho
            </a>
            @else
            <a href="#" wire:click.prevent="submit('awaiting-approval')" wire:confirm-modal="Enviar para assinatura | Deseja enviar o processo para assinatura agora, os signatário serão notificados por e-mail?" class="flex-1 md:w-auto inline-flex items-center justify-center gap-1.5 rounded-md bg-primary px-6 py-3 text-xs md:whitespace-nowrap font-semibold uppercase tracking-wide text-text-soft shadow-lg transition hover:brightness-110">
                <i class="lab la-telegram-plane text-lg"></i>
                Enviar para assinatura
            </a>
            <a href="#" wire:click.prevent="submit('draft')" class="flex-1 md:w-auto inline-flex items-center justify-center gap-1.5 rounded-md bg-primary/25 px-6 py-3 text-xs md:whitespace-nowrap font-semibold uppercase tracking-wide text-text-soft shadow-lg transition hover:brightness-110">
                <i class="las la-save text-lg"></i>
                Salvar Rescunho
            </a>
            @endif
        </div>
    </div>

    @teleport('body')
    <div>
        <livewire:panel.process.modal-files-upload />
        <livewire:panel.process.modal-signer />
    </div>
    @endteleport

</div>

<style>
    /* item arrastando (placeholder) */
    [data-sortable-item].draggable-source--is-dragging {
        opacity: 0.20;
        transform: scale(0.98);
    }

    /* esconder handle */
    [data-sortable-item].draggable-source--is-dragging .drag-handle {
        display: none !important;
    }

    /* clone */
    .draggable-mirror {
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
    }

    /* hover de drop (usando sua cor principal) */
    [data-sortable-item].draggable-over {
        border: 2px dashed rgba(26, 218, 209, 0.7);
    }
</style>