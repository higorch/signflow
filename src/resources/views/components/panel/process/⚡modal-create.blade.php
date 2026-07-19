<?php

use App\Models\Category;
use App\Models\Process;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public array $form = [
        'category' => '',
        'title' => '',
    ];

    public function render()
    {
        return $this->view([
            'categories' => $this->categories
        ]);
    }

    public function exception($e, $stopPropagation)
    {
        if ($e instanceof ValidationException) {
            $this->dispatch('errors-modal-process-create', errors: $this->getErrorBag());
            $this->errorToastErrorBag();
        }
    }

    #[Computed]
    public function categories()
    {
        return Category::where('taxonomy', 'process')->get();
    }

    public function submit()
    {
        $this->validate();

        try {
            $title = preg_replace('/\p{So}+/u', '', data_get($this, 'form.title'));

            $process = Process::create([
                'owner_id' => Auth::id(),
                'category_id' => data_get($this, 'form.category'),
                'reference' => yearNumberRandom(),
                'title' => $title,
                'status' => 'draft'
            ]);

            session()->flash('success', 'Processo criado com sucesso, agora configure o restante.');

            return $this->redirectRoute('panel.processes.edit', parameters: [
                'process' => $process->id
            ], navigate: true);
        } catch (\Exception $e) {
            Log::channel('process')->error('Erro ao criar processo', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            $this->dispatch('notify', msg: "Não foi possível criar.", type: "error");
        }
    }

    protected function prepareForValidation($attributes)
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

<div wire:ignore.self class="fixed inset-0 overflow-y-auto bg-black/60 invisible" x-data="modal('modal-process-create')" x-bind="events" :class="{'visible': open, 'invisible': !open}">

    <div class="flex items-center justify-center min-h-dvh p-6" @click.self="open = true">

        <div wire:loading.class="loading-box-fade" class="relative w-full max-w-2xl rounded-md shadow-lg bg-card" x-show="open" x-transition>

            <span class="absolute top-4 right-4 text-lg cursor-pointer text-text-muted hover:text-red-500" @click.prevent="open = false">
                <i class="las la-times"></i>
            </span>

            {{-- HEADER --}}
            <div class="flex items-center w-full p-4 border-b border-border/40">
                <p class="font-semibold text-lg text-text-soft">Novo processo</p>
            </div>

            {{-- BODY --}}
            <div wire:keydown.enter="submit" class="flex flex-col grow p-4">

                <div class="grid grid-cols-12 gap-6">

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

                </div>

            </div>

            {{-- FOOTER --}}
            <div class="flex gap-4 w-full p-4 border-t border-border/40">
                <a href="#" @click.prevent="open = false" class="flex-1 btn-secuondary">
                    <i class="las la-times text-lg"></i>Fechar
                </a>
                <a href="#" wire:click.prevent="submit" class="flex-1 btn-primary">
                    <i class="las la-plus text-lg"></i> Criar Novo
                </a>
            </div>

        </div>

    </div>

</div>