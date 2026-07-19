<?php

use App\Models\Attachment;
use App\Models\Category;
use App\Models\Process;
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
    }

    #[On('sort-files')]
    public function reorderFiles(?array $ids)
    {
        collect($ids)->unique()->values()->each(function ($id, $index) {
            $this->process->processFiles()->where('id', $id)->update([
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
            ->orderByRaw('CASE WHEN sort IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sort')
            ->orderBy('created_at')
            ->get();
    }

    public function submit()
    {
        $this->validate();

        try {
            //
        } catch (\Exception $e) {
            Log::channel('process')->error('Erro ao salvar', ['message' => $e->getMessage()]);
            $this->dispatch('notify', msg: "Não foi possível salvar.", type: "error");
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

    protected function prepareForValidation($attributes): array
    {
        return $attributes;
    }

    protected function rules()
    {
        return [
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

                    $parts = preg_split('/\s+/', $value);

                    foreach ($parts as $part) {
                        if (! preg_match('/^[A-Za-zÀ-ÿ]+$/u', $part)) {
                            $fail(__('validation.sem_caracteres'));
                            return;
                        }
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
            <a href="#" wire:click.prevent="submit" class="flex-1 md:w-auto inline-flex items-center justify-center gap-1.5 rounded-md bg-primary px-6 py-3 text-xs font-semibold uppercase tracking-wide text-text-soft shadow-lg transition hover:brightness-110">
                <i class="las la-save text-lg"></i>
                Salvar
            </a>
        </div>
    </div>

    {{-- BODY --}}
    <div class="flex flex-col grow gap-4">

        {{-- FORMULÁRIO --}}
        <div class="col-span-12 md:col-span-12 grid grid-cols-12 gap-4 rounded-md p-4 border border-border bg-card shadow-xl">

            {{-- CATEGORY --}}
            <div class="relative col-span-full md:col-span-12 flex flex-col gap-2">
                <label class="label-input-basic">Categoria</label>
                <select x-data="choices($wire.entangle('form.category'), '---', '', 'auto', false)">
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->title }}</option>
                    @endforeach
                </select>
                @error('form.category') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full label">{{ $message }}</span> @enderror
            </div>

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
                    @error('form.description') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full h-full">{{ $message }}</span> @enderror
                </div>
            </div>

        </div>

        {{-- FILES --}}
        <div class="col-span-12 md:col-span-12 grid grid-cols-12 gap-4 rounded-md p-4 border border-border bg-card shadow-xl">

            <div class="col-span-full md:col-span-12 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
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
            <div x-data="sortable('files')" class="col-span-full md:col-span-12 flex flex-col gap-4">

                @foreach($processFiles as $file)

                @php
                $isPdf = $file->extension === 'pdf';
                $url = $file->signed_url;
                @endphp

                <div data-sortable-item="{{ $file->id }}" wire:key="file-{{ $file->id }}" wire:loading.class="loading-box-fade" wire:target="removeFile('{{ $file->id }}')" class="group flex justify-between gap-3 p-3 rounded-md border border-border/25 bg-card">

                    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="group relative flex h-18 w-26 items-center justify-center overflow-hidden rounded-md border border-border/85 bg-surface-active transition-all duration-200 group-hover:border-primary group-hover:shadow-md md:h-20 md:w-32">
                        @if($isPdf)
                        <div class="flex h-full w-full flex-col items-center justify-center gap-1">
                            <i class="las la-file-pdf text-4xl text-red-500"></i>
                            <span class="text-[10px] font-medium text-text">PDF</span>
                        </div>
                        @else
                        <img src="{{ $url }}" class="h-full w-full object-cover">
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
                                <i class="las la-file-pdf text-sm text-red-500"></i>
                                <span class="text-xs text-text">PDF</span>
                                @break

                                @default
                                <i class="las la-file-image text-sm text-text-muted"></i>
                                <span class="text-xs text-text">Imagem</span>
                                @endswitch
                            </span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-text-muted">{{ maskFormat('file_size', $file->size) }}</span>
                            <div class="flex items-center gap-3">
                                <button type="button" @click.prevent data-sortable-handle title="Arrastar" class="inline-flex items-center justify-center rounded-md cursor-grab border border-primary/80 bg-primary/25 px-3 py-2 text-[10px] uppercase tracking-wide text-text transition hover:bg-primary/40">
                                    <i class="las la-arrows-alt text-base"></i>
                                </button>
                                <a href="#" wire:click.prevent="removeFile('{{ $file->id }}')" wire:confirm-modal="Excluir arquivo | Deseja realmente excluir o arquivo permanentemente?" title="Remover" class="inline-flex items-center justify-center rounded-md border border-primary/80 bg-primary/25 px-3 py-2 text-[10px] uppercase tracking-wide text-text transition hover:bg-primary/40">
                                    <i class="las la-times text-base"></i>
                                </a>
                            </div>
                        </div>

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
        <div class="col-span-12 md:col-span-12 grid grid-cols-12 gap-4 rounded-md p-4 border border-border bg-card shadow-xl">

            <div class="col-span-full md:col-span-12 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
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

            <div class="col-span-full md:col-span-12 alert alert-info flex items-center justify-between">
                <div class="flex items-start gap-2">
                    <div class="alert-icon"><i class="las la-info-circle"></i></div>
                    <div class="alert-content leading-normal">Nenhum Signatário.</div>
                </div>
            </div>

        </div>

        {{-- HISTORY --}}
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
        <a href="#" wire:click.prevent="submit" class="md:w-auto inline-flex items-center justify-center gap-1.5 rounded-md bg-primary px-6 py-3 text-xs font-semibold uppercase tracking-wide text-text-soft shadow-lg transition hover:brightness-110">
            <i class="las la-save text-lg"></i>
            Salvar
        </a>
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