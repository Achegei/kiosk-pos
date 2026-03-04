<?php

namespace App\Jobs;

use App\Imports\ProductsImport;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ImportProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $tenant_id;

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath, int $tenant_id)
    {
        $this->filePath = $filePath;
        $this->tenant_id = $tenant_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Import products using the chunked import class
            Excel::import(new ProductsImport($this->tenant_id), $this->filePath);

            Log::info("Products imported successfully for tenant {$this->tenant_id}");

            // Delete the uploaded file after processing
            $relativePath = str_replace(storage_path('app/'), '', $this->filePath);
            Storage::delete($relativePath);

        } catch (\Throwable $e) {
            Log::error("Product import failed for tenant {$this->tenant_id}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}