<?php

use App\Models\ProcessSigner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public ?string $processSignerId = null;

    public array $form = [
        'rejection_reason' => '',
    ];

    public function render()
    {
        return $this->view();
    }

    public function exception($e, $stopPropagation)
    {
        if ($e instanceof ValidationException) {
            $this->dispatch('errors-modal-process-rejected', errors: $this->getErrorBag());
            $this->errorToastErrorBag();
        }
    }

    #[On('opened.modal-process-rejected')]
    public function openModalProcessRejected($payload)
    {
        $this->processSignerId = $payload['processSignerId'] ?? null;
    }

    #[Computed]
    public function processSigner()
    {
        if (is_null($this->processSignerId)) return null;

        return ProcessSigner::with('process')->find($this->processSignerId);
    }

    public function submit()
    {
        $this->validate();

        DB::beginTransaction();

        try {
            $this->processSigner->update([
                'status' => 'rejected',
                'rejection_reason' => trim($this->form['rejection_reason']),
                'action_at' => now(),
                'action_ip' => request()->ip(),
                'action_agent' => request()->userAgent(),
            ]);

            $this->processSigner->process->update([
                'status' => 'failed',
            ]);

            DB::commit();

            $this->dispatch('notify', msg: 'Processo rejeitado com sucesso.', type: 'success');

            return redirect()->to(URL::temporarySignedRoute('signer.process', now()->addMinutes(30), [
                'processSigner' => $this->processSigner->id,
            ]));
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::channel('process')->error('Erro ao rejeitar processo', [
                'process_id' => $this->processSigner->process_id,
                'process_signer_id' => $this->processSigner->id,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            $this->dispatch('notify', msg: 'Não foi possível rejeitar o processo.', type: 'error');
        }
    }

    protected function prepareForValidation($attributes)
    {
        return $attributes;
    }

    protected function rules()
    {
        return [
            'form.rejection_reason' => [
                'required',
                'min:10',
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

<div wire:ignore.self class="fixed inset-0 overflow-y-auto bg-black/60 invisible" x-data="modal('modal-process-rejected')" x-bind="events" :class="{'visible': open, 'invisible': !open}">

    <div class="flex items-center justify-center min-h-dvh p-6" @click.self="open = true">

        <div wire:loading.class="loading-box-fade" class="relative w-full max-w-2xl rounded-md shadow-lg bg-card" x-show="open" x-transition>

            <span class="absolute top-4 right-4 text-lg cursor-pointer text-text-muted hover:text-red-500" @click.prevent="open = false">
                <i class="las la-times"></i>
            </span>

            {{-- HEADER --}}
            <div class="flex items-center w-full p-4 border-b border-border/40">
                <p class="font-semibold text-lg text-text-soft">Discordo, não assino</p>
            </div>

            {{-- BODY --}}
            <div wire:keydown.enter="submit" class="flex flex-col grow p-4">

                <div class="grid grid-cols-12 gap-6">

                    {{-- MOTIVO --}}
                    <div class="relative col-span-full md:col-span-12 flex flex-col gap-2">
                        <label class="label-input-basic">Motivo</label>
                        <div class="relative">
                            <textarea wire:model="form.rejection_reason" class="input-basic resize-none h-20"></textarea>
                            <x-global.limit-input :limit="190" :model="'form.rejection_reason'" :stop="true" :align="'bottom'" />
                            @error('form.rejection_reason') <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full h-full label">{{ $message }}</span> @enderror
                        </div>
                    </div>

                </div>

            </div>

            {{-- FOOTER --}}
            <div class="flex gap-4 w-full p-4 border-t border-border/40">
                <a href="#" @click.prevent="open = false" class="flex-1 btn-secondary">
                    <i class="las la-times text-lg"></i>Fechar
                </a>
                <a href="#" wire:click.prevent="submit" class="flex-1 btn-primary">
                    <i class="las la-save text-lg"></i> Salvar
                </a>
            </div>

        </div>

    </div>

</div>