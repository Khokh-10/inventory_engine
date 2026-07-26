<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InventoryState;
use App\Enums\TransactionType;
use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\InvalidInventoryStateException;
use App\Exceptions\InventoryTransferException;
use App\Models\Inventory;
use App\Models\InventoryLedger;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Reserve inventory for an order.
     */
    public function reserve(int $inventoryId, int $quantity, string $referenceType, string $referenceId, ?string $performedBy = null, ?string $notes = null): Inventory
    {
        return DB::transaction(function () use ($inventoryId, $quantity, $referenceType, $referenceId, $performedBy, $notes): Inventory {
            $inventory = $this->lockInventory($inventoryId);

            $this->validateAvailableQuantity($inventory, $quantity, TransactionType::RESERVE);

            $inventory->available_quantity -= $quantity;
            $inventory->reserved_quantity += $quantity;
            $this->incrementVersion($inventory);
            $inventory->save();

            $this->createLedgerEntry(
                inventory: $inventory,
                transactionType: TransactionType::RESERVE,
                fromState: InventoryState::AVAILABLE,
                toState: InventoryState::RESERVED,
                quantity: $quantity,
                referenceType: $referenceType,
                referenceId: $referenceId,
                performedBy: $performedBy,
                notes: $notes,
            );

            return $inventory;
        });
    }

    /**
     * Release reserved inventory back to available.
     */
    public function release(int $inventoryId, int $quantity, string $referenceType, string $referenceId, ?string $performedBy = null, ?string $notes = null): Inventory
    {
        return DB::transaction(function () use ($inventoryId, $quantity, $referenceType, $referenceId, $performedBy, $notes): Inventory {
            $inventory = $this->lockInventory($inventoryId);
            $this->validateBucketQuantity($inventory, TransactionType::RELEASE, $quantity, InventoryState::RESERVED);

            $inventory->reserved_quantity -= $quantity;
            $inventory->available_quantity += $quantity;
            $this->incrementVersion($inventory);
            $inventory->save();

            $this->createLedgerEntry(
                inventory: $inventory,
                transactionType: TransactionType::RELEASE,
                fromState: InventoryState::RESERVED,
                toState: InventoryState::AVAILABLE,
                quantity: $quantity,
                referenceType: $referenceType,
                referenceId: $referenceId,
                performedBy: $performedBy,
                notes: $notes,
            );

            return $inventory;
        });
    }

    /**
     * Pick reserved inventory.
     */
    public function pick(int $inventoryId, int $quantity, string $referenceType, string $referenceId, ?string $performedBy = null, ?string $notes = null): Inventory
    {
        return DB::transaction(function () use ($inventoryId, $quantity, $referenceType, $referenceId, $performedBy, $notes): Inventory {
            $inventory = $this->lockInventory($inventoryId);
            $this->validateBucketQuantity($inventory, TransactionType::PICK, $quantity, InventoryState::RESERVED);

            $inventory->reserved_quantity -= $quantity;
            $inventory->picked_quantity += $quantity;
            $this->incrementVersion($inventory);
            $inventory->save();

            $this->createLedgerEntry(
                inventory: $inventory,
                transactionType: TransactionType::PICK,
                fromState: InventoryState::RESERVED,
                toState: InventoryState::PICKED,
                quantity: $quantity,
                referenceType: $referenceType,
                referenceId: $referenceId,
                performedBy: $performedBy,
                notes: $notes,
            );

            return $inventory;
        });
    }

    /**
     * Confirm shipment (moves picked -> shipped).
     */
    public function ship(int $inventoryId, int $quantity, string $referenceType, string $referenceId, ?string $performedBy = null, ?string $notes = null): Inventory
    {
        return DB::transaction(function () use ($inventoryId, $quantity, $referenceType, $referenceId, $performedBy, $notes): Inventory {
            $inventory = $this->lockInventory($inventoryId);
            $this->validateBucketQuantity($inventory, TransactionType::SHIP, $quantity, InventoryState::PICKED);

            $inventory->picked_quantity -= $quantity;
            $inventory->shipped_quantity += $quantity;
            $this->incrementVersion($inventory);
            $inventory->save();

            $this->createLedgerEntry(
                inventory: $inventory,
                transactionType: TransactionType::SHIP,
                fromState: InventoryState::PICKED,
                toState: InventoryState::SHIPPED,
                quantity: $quantity,
                referenceType: $referenceType,
                referenceId: $referenceId,
                performedBy: $performedBy,
                notes: $notes,
            );

            return $inventory;
        });
    }

    /**
     * Return shipped inventory back to available.
     */
    public function return(int $inventoryId, int $quantity, string $referenceType, string $referenceId, ?string $performedBy = null, ?string $notes = null): Inventory
    {
        return DB::transaction(function () use ($inventoryId, $quantity, $referenceType, $referenceId, $performedBy, $notes): Inventory {
            $inventory = $this->lockInventory($inventoryId);
            $this->validateBucketQuantity($inventory, TransactionType::RETURN, $quantity, InventoryState::SHIPPED);

            $inventory->shipped_quantity -= $quantity;
            $inventory->available_quantity += $quantity;
            $this->incrementVersion($inventory);
            $inventory->save();

            $this->createLedgerEntry(
                inventory: $inventory,
                transactionType: TransactionType::RETURN,
                fromState: InventoryState::SHIPPED,
                toState: InventoryState::AVAILABLE,
                quantity: $quantity,
                referenceType: $referenceType,
                referenceId: $referenceId,
                performedBy: $performedBy,
                notes: $notes,
            );

            return $inventory;
        });
    }

    /**
     * Transfer available inventory between warehouses.
     *
     * Locks are always acquired in ascending inventory_id order to prevent
     * deadlocks when two transfers run concurrently in opposite directions
     * (e.g. A -> B and B -> A at the same time).
     *
     * @return array{0: Inventory, 1: Inventory} [source, destination]
     */
    public function transfer(int $sourceInventoryId, int $destinationInventoryId, int $quantity, string $referenceType, string $referenceId, ?string $performedBy = null, ?string $notes = null): array
    {
        if ($sourceInventoryId === $destinationInventoryId) {
            throw new InventoryTransferException('Source and destination inventories must be different.');
        }

        return DB::transaction(function () use ($sourceInventoryId, $destinationInventoryId, $quantity, $referenceType, $referenceId, $performedBy, $notes): array {
            // Lock in a deterministic order (ascending ID) regardless of
            // transfer direction, so two opposite-direction transfers can
            // never wait on each other in a circular fashion.
            $orderedIds = [$sourceInventoryId, $destinationInventoryId];
            sort($orderedIds);

            $locked = [
                $orderedIds[0] => $this->lockInventory($orderedIds[0]),
                $orderedIds[1] => $this->lockInventory($orderedIds[1]),
            ];

            $sourceInventory = $locked[$sourceInventoryId];
            $destinationInventory = $locked[$destinationInventoryId];

            $this->validateAvailableQuantity($sourceInventory, $quantity, TransactionType::TRANSFER_OUT);

            $sourceInventory->available_quantity -= $quantity;
            $destinationInventory->available_quantity += $quantity;

            $this->incrementVersion($sourceInventory);
            $this->incrementVersion($destinationInventory);

            $sourceInventory->save();
            $destinationInventory->save();

            $this->createLedgerEntry(
                inventory: $sourceInventory,
                transactionType: TransactionType::TRANSFER_OUT,
                fromState: InventoryState::AVAILABLE,
                toState: InventoryState::AVAILABLE,
                quantity: $quantity,
                referenceType: $referenceType,
                referenceId: $referenceId,
                performedBy: $performedBy,
                notes: $notes,
            );

            $this->createLedgerEntry(
                inventory: $destinationInventory,
                transactionType: TransactionType::TRANSFER_IN,
                fromState: InventoryState::AVAILABLE,
                toState: InventoryState::AVAILABLE,
                quantity: $quantity,
                referenceType: $referenceType,
                referenceId: $referenceId,
                performedBy: $performedBy,
                notes: $notes,
            );

            return [$sourceInventory, $destinationInventory];
        });
    }

    /**
     * Lock an inventory row for update.
     */
    private function lockInventory(int $inventoryId): Inventory
    {
        return Inventory::query()
            ->whereKey($inventoryId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Validate that there is enough available stock for a movement
     * originating from the "available" bucket (reserve, transfer out).
     */
    private function validateAvailableQuantity(Inventory $inventory, int $quantity, TransactionType $transactionType): void
    {
        if ($quantity <= 0) {
            throw new InvalidInventoryStateException(sprintf('Quantity must be greater than zero for %s.', $transactionType->value));
        }

        if ($inventory->available_quantity < $quantity) {
            throw new InsufficientInventoryException(sprintf('Insufficient available quantity for %s.', $transactionType->value));
        }
    }

    /**
     * Validate that the specific quantity bucket (reserved/picked/shipped)
     * has enough quantity for the requested movement.
     *
     * NOTE: This intentionally does NOT infer a single "current state" for
     * the inventory row as a whole. A single inventory row can legitimately
     * have available_quantity > 0 AND reserved_quantity > 0 AND
     * picked_quantity > 0 at the same time (e.g. 10 total: 4 available,
     * 3 reserved, 3 picked). Validation must always check the specific
     * bucket relevant to the transition being performed, not a derived
     * "overall" state - otherwise legitimate transitions get rejected
     * whenever more than one bucket is non-zero.
     */
    private function validateBucketQuantity(Inventory $inventory, TransactionType $transactionType, int $quantity, InventoryState $expectedBucket): void
    {
        if ($quantity <= 0) {
            throw new InvalidInventoryStateException(sprintf('Quantity must be greater than zero for %s.', $transactionType->value));
        }

        $bucketQuantity = match ($expectedBucket) {
            InventoryState::RESERVED => $inventory->reserved_quantity,
            InventoryState::PICKED => $inventory->picked_quantity,
            InventoryState::SHIPPED => $inventory->shipped_quantity,
            default => 0,
        };

        if ($bucketQuantity < $quantity) {
            throw new InsufficientInventoryException(sprintf('Insufficient %s quantity for %s.', $expectedBucket->value, $transactionType->value));
        }
    }

    /**
     * Increment inventory version (optimistic-lock style audit counter).
     */
    private function incrementVersion(Inventory $inventory): void
    {
        $inventory->version += 1;
    }

    /**
     * Create an immutable ledger entry.
     */
    private function createLedgerEntry(Inventory $inventory, TransactionType $transactionType, InventoryState $fromState, InventoryState $toState, int $quantity, string $referenceType, string $referenceId, ?string $performedBy, ?string $notes): void
    {
        InventoryLedger::query()->create([
            'inventory_id' => $inventory->id,
            'transaction_type' => $transactionType->value,
            'from_state' => $fromState->value,
            'to_state' => $toState->value,
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'performed_by' => $performedBy,
            'notes' => $notes,
            'created_at' => now(),
        ]);
    }
}