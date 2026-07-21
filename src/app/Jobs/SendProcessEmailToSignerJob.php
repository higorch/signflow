<?php

namespace App\Jobs;

use App\Models\Process;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Notifications\Process\ProcessSignatureNotification;

class SendProcessEmailToSignerJob implements ShouldQueue
{
    use Queueable;

    public string $processId;

    public int $tries = 5;

    /**
     * Create a new job instance.
     */
    public function __construct(string $processId)
    {
        $this->processId = $processId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $process = Process::with('signers.user')->findOrFail($this->processId);

        foreach ($process->signers as $signer) {
            $signer->user->notify(new ProcessSignatureNotification($process, $signer));
        }
    }
}
