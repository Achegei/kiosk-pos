<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\OfflineSaleController;
use Illuminate\Http\Request;
use App\Models\Inventory;
use App\Models\OfflineSale;

class TestOfflineSync extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:offline-sync';

    /**
     * The console command description.
     */
    protected $description = 'Test syncing offline sales locally via OfflineSaleController';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting offline sale sync test...");

        // 1️⃣ Create sample test sale
        $testSales = [
            [
                'device_uuid' => 'TEST-DEVICE-123',
                'user_id' => 1,
                'items' => [
                    ['product_id' => Inventory::first()->product_id ?? 1, 'quantity' => 1, 'price' => 100]
                ]
            ]
        ];

        // 2️⃣ Call controller directly
        $controller = new OfflineSaleController();
        $request = Request::create('/offline-sync', 'POST', ['sales' => $testSales]);

        $response = $controller->sync($request);

        // 3️⃣ Decode JSON response
        $data = json_decode($response->getContent(), true);

        if ($data['status'] === 'success') {
            $this->info("✅ Test passed! Offline sale synced successfully.");
            $this->info("Synced IDs: " . implode(', ', $data['synced_ids']));
        } else {
            $this->error("❌ Test failed: " . ($data['message'] ?? 'Unknown error'));
        }

        $this->info("Offline sync test completed.");
    }
}
