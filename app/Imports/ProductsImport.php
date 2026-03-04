<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\DB;

class ProductsImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected $tenant_id;

    public function __construct($tenant_id)
    {
        $this->tenant_id = $tenant_id;
    }

    /**
     * Process each chunk of rows
     */
    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {

                // Skip invalid rows
                if (empty($row['sku']) || empty($row['name'])) {
                    continue;
                }

                // Update existing product or create new one
                $product = Product::updateOrCreate(
                    [
                        'sku'       => $row['sku'],
                        'tenant_id' => $this->tenant_id
                    ],
                    [
                        'name'      => $row['name'],
                        'price'     => $row['price'] ?? 0,
                        'is_active' => 1,
                    ]
                );

                // Update inventory
                if (isset($row['quantity'])) {
                    Inventory::updateOrCreate(
                        ['product_id' => $product->id],
                        [
                            'quantity'  => $row['quantity'],
                            'tenant_id' => $this->tenant_id,
                        ]
                    );
                }
            }
        });
    }

    /**
     * Define the chunk size for memory efficiency
     */
    public function chunkSize(): int
    {
        return 2000; // process 2000 rows at a time
    }
}