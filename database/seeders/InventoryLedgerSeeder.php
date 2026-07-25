<?php

namespace Database\Seeders;

use App\Enums\TransactionType;
use App\Models\Inventory;
use App\Models\InventoryLedger;
use Illuminate\Database\Seeder;

class InventoryLedgerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $inventories = Inventory::query()->get();

        foreach ($inventories as $inventory) {
            $transactionTypes = [
                TransactionType::INITIAL_INTAKE,
                TransactionType::RESERVE,
                TransactionType::RELEASE,
                TransactionType::PICK,
                TransactionType::SHIP,
                TransactionType::RETURN,
                TransactionType::TRANSFER_IN,
                TransactionType::TRANSFER_OUT,
                TransactionType::ADJUSTMENT_IN,
                TransactionType::ADJUSTMENT_OUT,
            ];

            foreach (array_slice($transactionTypes, 0, 4) as $transactionType) {
                InventoryLedger::factory()->create([
                    'inventory_id' => $inventory->id,
                    'transaction_type' => $transactionType->value,
                    'from_state' => 'available',
                    'to_state' => 'reserved',
                    'quantity' => random_int(1, 20),
                    'reference_type' => 'reservation',
                    'reference_id' => (string) fake()->uuid(),
                    'performed_by' => fake()->optional()->userName(),
                    'notes' => fake()->optional()->sentence(),
                ]);
            }

            InventoryLedger::factory()->create([
                'inventory_id' => $inventory->id,
                'transaction_type' => TransactionType::INITIAL_INTAKE->value,
                'from_state' => null,
                'to_state' => 'available',
                'quantity' => $inventory->total_quantity,
                'reference_type' => 'initial_stock',
                'reference_id' => (string) $inventory->id,
                'performed_by' => 'system',
                'notes' => 'Initial inventory intake',
            ]);
        }
    }
}
