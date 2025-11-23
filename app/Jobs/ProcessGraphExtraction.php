<?php

namespace App\Jobs;

use App\Models\Graph;
use App\Services\EntityExtractionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessGraphExtraction implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Graph $graph
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(EntityExtractionService $extractionService): void
    {
        $extractionService->extractEntities($this->graph);
    }
}
