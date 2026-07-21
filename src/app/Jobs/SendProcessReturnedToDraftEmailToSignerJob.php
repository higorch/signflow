<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendProcessReturnedToDraftEmailToSignerJob implements ShouldQueue
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
        //
    }
}
