<?php

use App\Models\Post;
use App\Models\PostMedia;
use App\Models\Process;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Format;
use Intervention\Image\Laravel\Facades\Image;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public ?string $processId;
    public $media = [];

    public function render()
    {
        return $this->view();
    }

    public function exception($e, $stopPropagation)
    {
        if ($e instanceof ValidationException) {
            $this->dispatch('errors-media-upload', errors: $this->getErrorBag());
            $this->errorToastErrorBag();
        }
    }

    public function updatedMedia()
    {
        $this->validate();

        DB::beginTransaction();

        $paths = [];

        try {
            if ($this->post->post_media_count >= 8) {
                throw new \Exception(__('validation.max_media'), 422);
            }

            foreach ($this->media as $file) {
                $path = null;
                $ext = strtolower($file->getClientOriginalExtension());

                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
                $isMp4 = $ext === 'mp4';

                if ($isImage) {
                    $path = AttachmentPath::make('creator/content/images', 'webp');
                    $paths[] = $path;

                    $image = Image::decode($file->getRealPath());
                    $image->orient();
                    $image = ImageWatermark::apply($image);

                    Storage::disk('local')->put($path, $image->encodeUsingFormat(Format::WEBP, quality: 90));
                }

                if ($isMp4) {
                    $path = AttachmentPath::make('creator/content/videos', 'mp4');
                    $paths[] = $path;

                    Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));
                }

                $attachment = $this->post->attachments()->create([
                    'user_id' => Auth::id(),
                    'disk' => 'local',
                    'path' => $path,
                    'extension' => $isImage ? 'webp' : 'mp4',
                    'size' => $file->getSize(),
                    'taxonomy' => 'creator-media',
                    'status' => 'processing',
                    'moderation_status' => 'pending',
                ]);

                $attachment->postMedia()->create([
                    'post_id' => $this->post->id,
                    'attachment_id' => $attachment->id,
                    'delivery_type' => 'subscriber',
                ]);

                if ($isImage) {
                    dispatch(new \App\Jobs\ProcessImageJob($attachment->id, 'r2_private'))->afterCommit();
                }

                if ($isMp4) {
                    dispatch(new \App\Jobs\ProcessVideoJob($attachment->id, 'r2_private'))->afterCommit();
                }
            }

            DB::commit();

            $this->js('$wire.$dispatch("close-modal", { ref: "modal-media-upload" })');

            $this->dispatch('refresh')->to('pages::hotfeed.content.save');
            $this->dispatch('notify', msg: 'Mídias importadas com sucesso.', type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();

            foreach ($paths as $path) {
                Storage::disk('local')->delete($path);
            }

            Log::channel('hotfeed')->error('Erro ao importar mídias', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            match ($e->getCode()) {
                422 => $this->dispatch('notify', msg: $e->getMessage(), type: 'warning'),
                default => $this->dispatch('notify', msg: 'Não foi possível importar.', type: 'error'),
            };
        } finally {
            foreach ($this->media ?? [] as $file) {
                $file->delete();
            }

            $this->reset('media');
        }
    }

    #[On('opened.modal-media-upload')]
    public function openeModalMediaUpload($payload)
    {
        $this->processId = $payload['processId'] ?? null;
    }

    #[Computed]
    public function process()
    {
        return Process::where('id', $this->processId)->withCount([
            'media'
        ])->with([
            'media'
        ])->first();
    }

    protected function rules()
    {
        return [
            'media' => "required",
            'media.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:102400'
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

<div wire:ignore.self class="fixed inset-0 overflow-y-auto bg-black/60 invisible" x-data="modal('modal-media-upload')" x-bind="events" :class="{'visible': open, 'invisible': !open}">

    <div class="flex items-center justify-center min-h-dvh p-6" @click.self="open = true">

        <div wire:loading.class="loading-box-fade" class="relative w-full max-w-2xl rounded-md shadow-lg bg-card" x-show="open" x-transition>

            <span class="absolute top-4 right-4 text-lg cursor-pointer text-gray-400 hover:text-red-500" @click.prevent="open = false">
                <i class="las la-times"></i>
            </span>

            {{-- HEADER --}}
            <div class="flex items-center w-full p-4 border-b border-[#fada82]/5">
                <p class="font-semibold text-lg text-text-soft">Escolha os Arquivos do Processo</p>
            </div>

            {{-- BODY --}}
            <div class="flex flex-col grow p-4 overflow-y-auto">

                <label class="flex flex-col w-full cursor-pointer">
                    <input type="file" wire:model="media" accept="image/*,.pdf,application/pdf" class="hidden" multiple>
                    <div class="flex grow rounded-md border border-dashed border-border bg-surface p-4 transition-colors duration-300 hover:border-primary hover:bg-card">
                        <div class="flex grow items-center justify-center rounded-md border border-border bg-card-hover/50 p-4">
                            <div class="text-center">
                                <div class="mx-auto mb-3 flex size-10 items-center justify-center rounded-full border border-primary/80 bg-primary/25 transition hover:bg-primary/40">
                                    <i class="las la-cloud-upload-alt text-xl text-text"></i>
                                </div>
                                <p class="mb-1 text-[10px] font-medium text-text">
                                    JPG • JPEG • PNG • WEBP • PDF
                                </p>
                                <p class="text-sm text-text-muted">
                                    Máx. 100 MB por arquivo
                                </p>
                                <p class="mt-1 text-sm font-medium text-text-soft">
                                    Selecione arquivos para o processo
                                </p>
                            </div>
                        </div>
                    </div>
                </label>

            </div>

            {{-- FOOTER --}}
            <div class="flex gap-4 w-full p-4 border-t border-border/40">
                <a href="#" @click.prevent="open = false" class="flex-1 btn-secuondary">
                    <i class="las la-times text-lg"></i>Fechar
                </a>
            </div>

        </div>

    </div>

</div>