<?php

use App\Models\Process;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Format;
use Intervention\Image\Alignment;
use Intervention\Image\Laravel\Facades\Image;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public ?string $processId = null;
    public int $maxSizeMb = 50;

    public $files = [];

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

    public function updatedFiles()
    {
        $this->validate();

        DB::beginTransaction();

        $paths = [];

        try {
            foreach ($this->files as $file) {
                $ext = strtolower($file->getClientOriginalExtension());

                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
                $isPdf = $ext === 'pdf';

                $path = $isPdf ? $this->processPdf($file) : $this->processImage($file);
                $paths[] = $path;

                $size = Storage::disk('local')->size($path);

                $this->process->attachments()->create([
                    'user_id' => Auth::id(),
                    'disk' => 'local',
                    'path' => $path,
                    'extension' => $isImage ? 'webp' : 'pdf',
                    'size' => $size,
                    'taxonomy' => 'process',
                    'status' => 'active',
                ]);
            }

            DB::commit();

            $this->js('$wire.$dispatch("close-modal", { ref: "modal-process-files-upload" })');

            $this->dispatch('refresh')->to('pages::panel.process.save');
            $this->dispatch('notify', msg: 'Arquivos importados com sucesso.', type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();

            foreach ($paths as $path) {
                Storage::disk('local')->delete($path);
            }

            Log::channel('process')->error('Erro ao importar arquivos', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            match ($e->getCode()) {
                422 => $this->dispatch('notify', msg: $e->getMessage(), type: 'warning'),
                default => $this->dispatch('notify', msg: 'Não foi possível importar.', type: 'error'),
            };
        } finally {
            foreach ($this->files ?? [] as $file) {
                $file->delete();
            }

            $this->reset('files');
        }
    }

    #[On('opened.modal-process-files-upload')]
    public function openModalFilesUpload($payload)
    {
        $this->processId = $payload['processId'] ?? null;
    }

    #[Computed]
    public function process()
    {
        if (is_null($this->processId)) return null;

        return Process::where('id', $this->processId)->withCount([
            'processFiles'
        ])->with([
            'processFiles'
        ])->first();
    }

    protected function processImage(UploadedFile $file): string
    {
        $image = Image::decode($file->getRealPath());

        $path = AttachmentPath::make('process/images', 'webp');
        $maxSize = $this->maxSizeMb * 1024 * 1024;

        $image->orient();

        $image = ImageWatermark::apply(
            image: $image,
            transparency: 0.5,
            width: 0.25, // 20% da largura da imagem
            alignment: Alignment::BOTTOM_RIGHT,
            offsetX: 20,
            offsetY: 20,
        );

        $content = $image->encodeUsingFormat(Format::WEBP, quality: 90)->toString();

        if (strlen($content) > $maxSize) throw new \Exception("A imagem deve possuir no máximo {$this->maxSizeMb} MB.", 422);

        Storage::disk('local')->put($path, $content);

        return $path;
    }

    protected function processPdf(UploadedFile $file): string
    {
        $softCompressedPath = null;
        $hardCompressedPath = null;

        try {
            if (!$file instanceof TemporaryUploadedFile) throw new \Exception('Arquivo inválido.', 422);

            $inputPath = $file->getRealPath();

            if (!$inputPath || !is_file($inputPath)) throw new \Exception('Arquivo temporário não encontrado.', 422);

            $ghostscript = config('singflow.ghostscript_path');

            if (empty($ghostscript) || !is_file($ghostscript)) throw new \Exception('Ghostscript não configurado corretamente.');

            $path = AttachmentPath::make('process/pdf', 'pdf');

            $maxSize = $this->maxSizeMb * 1024 * 1024;

            $softCompressedPath = storage_path('app/pdf_soft_' . uniqid('', true) . '.pdf');

            $command = sprintf(
                '%s -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook -dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s %s',
                escapeshellarg($ghostscript),
                escapeshellarg($softCompressedPath),
                escapeshellarg($inputPath),
            );

            exec($command, $output, $code);

            if ($code !== 0 || !is_file($softCompressedPath) || filesize($softCompressedPath) < 1000) throw new \Exception('Falha ao comprimir o PDF.');

            $finalPath = $softCompressedPath;

            if (filesize($softCompressedPath) > $maxSize) {
                $hardCompressedPath = storage_path('app/pdf_hard_' . uniqid('', true) . '.pdf');

                $command = sprintf(
                    '%s -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/screen -dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s %s',
                    escapeshellarg($ghostscript),
                    escapeshellarg($hardCompressedPath),
                    escapeshellarg($inputPath),
                );

                exec($command, $output, $code);

                if ($code !== 0 || !is_file($hardCompressedPath) || filesize($hardCompressedPath) < 1000) throw new \Exception('Falha na compressão agressiva do PDF.');

                $finalPath = $hardCompressedPath;
            }

            if (filesize($finalPath) > $maxSize) throw new \Exception("O PDF deve possuir no máximo {$this->maxSizeMb} MB.", 422);

            Storage::disk('local')->put($path, fopen($finalPath, 'rb'));

            return $path;
        } finally {
            foreach ([$softCompressedPath, $hardCompressedPath] as $tempFile) {
                if ($tempFile && is_file($tempFile)) {
                    unlink($tempFile);
                }
            }
        }
    }

    protected function rules()
    {
        return [
            'files' => "required",
            'files.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:51200' // max 50MB
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

<div wire:ignore.self class="fixed inset-0 overflow-y-auto bg-black/60 invisible" x-data="modal('modal-process-files-upload')" x-bind="events" :class="{'visible': open, 'invisible': !open}">

    <div class="flex items-center justify-center min-h-dvh p-6" @click.self="open = true">

        <div wire:loading.class="loading-box-fade" class="relative w-full max-w-2xl rounded-md shadow-lg bg-card" x-show="open" x-transition>

            <span class="absolute top-4 right-4 text-lg cursor-pointer text-text-muted hover:text-red-500" @click.prevent="open = false">
                <i class="las la-times"></i>
            </span>

            {{-- HEADER --}}
            <div class="flex items-center w-full p-4 border-b border-border/40">
                <p class="font-semibold text-lg text-text-soft">Escolha os Arquivos do Processo</p>
            </div>

            {{-- BODY --}}
            <div class="flex flex-col grow p-4 overflow-y-auto">

                <label class="flex flex-col w-full cursor-pointer">
                    <input type="file" wire:model="files" accept="image/*,.pdf,application/pdf" class="hidden" multiple>
                    <div class="flex grow rounded-md border border-dashed border-border bg-surface p-4 transition-colors duration-300 hover:border-primary hover:bg-card">
                        <div class="relative flex grow items-center justify-center rounded-md border border-border bg-card-hover/50 p-4">
                            <div class="text-center">
                                <div class="mx-auto mb-3 flex size-10 items-center justify-center rounded-full border border-primary/80 bg-primary/25 transition hover:bg-primary/40">
                                    <i class="las la-cloud-upload-alt text-xl text-text"></i>
                                </div>
                                <p class="mb-1 text-[10px] font-medium text-text">
                                    JPG • JPEG • PNG • WEBP • PDF
                                </p>
                                <p class="text-sm text-text-muted">
                                    Máx. 50 MB por arquivo
                                </p>
                                <p class="mt-1 text-sm font-medium text-text-soft">
                                    Selecione arquivos para o processo
                                </p>
                            </div>
                            @if($errors->has('files') || $errors->has('files.*')) <span @mouseover="$el.remove()" @touchstart="$el.remove()" class="input-error full h-full">{{ $errors->first('files') ?: $errors->first('files.*') }}</span> @endif
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