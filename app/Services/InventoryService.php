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

            $fromState = $this->resolveState($inventory);
            $inventory->available_quantity -= $quantity;
            $inventory->reserved_quantity += $quantity;
            $this->incrementVersion($inventory);
            $inventory->save();

            $this->createLedgerEntry(
                inventory: $inventory,
                transactionType: TransactionType::RESERVE,
                fromState: $fromState,
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
     * Release reserved inventory.
     */
    public function release(int $inventoryId, int $quantity, string $referenceType, string $referenceId, ?string $performedBy = null, ?string $notes = null): Inventory
    {
        return DB::transaction(function () use ($inventoryId, $quantity, $referenceType, $referenceId, $performedBy, $notes): Inventory {
            $inventory = $this->lockInventory($inventoryId);
            $this->validateStateTransition($inventory, TransactionType::RELEASE, $quantity, InventoryState::RESERVED);

            $fromState = $this->resolveState($inventory);
            $inventory->reserved_quantity -= $quantity;
            $inventory->available_quantity += $quantity;
            $this->incrementVersion($inventory);
            $inventory->save();

            $this->createLedgerEntry(
                inventory: $inventory,
                transactionType: TransactionType::RELEASE,
                fromState: $fromState,
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
            $this->validateStateTransition($inventory, TransactionType::PICK, $quantity, InventoryState::RESERVED);

            $fromState = $this->resolveState($inventory);
            $inventory->reserved_quantity -= $quantity;
            $inventory->picked_quantity += $quantity;
            $this->incrementVersion($inventory);
            $inventory->save();

            $this->createLedgerEntry(
                inventory: $inventory,
                transactionType: TransactionType::PICK,
                fromState: $fromState,
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
     * Confirm shipment.
     */
    public function ship(int $inventoryId, int $quantity, string $referenceType, string $referenceId, ?string $performedBy = null, ?string $notes = null): Inventory
    {
        return DB::transaction(function () use ($inventoryId, $quantity, $referenceType, $referenceId, $performedBy, $notes): Inventory {
            $inventory = $this->lockInventory($inventoryId);
            $this->validateStateTransition($inventory, TransactionType::SHIP, $quantity, InventoryState::PICKED);

            $fromState = $this->resolveState($inventory);
            $inventory->picked_quantity -= $quantity;
            $inventory->shipped_quantity += $quantity;
            $this->incrementVersion($inventory);
            $inventory->save();

            $this->createLedgerEntry(
                inventory: $inventory,
                transactionType: TransactionType::SHIP,
                fromState: $fromState,
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
     * Return inventory.
     */
    public function return(int $inventoryId, int $quantity, string $referenceType, string $referenceId, ?string $performedBy = null, ?string $notes = null): Inventory
    {
        return DB::transaction(function () use ($inventoryId, $quantity, $referenceType, $referenceId, $performedBy, $notes): Inventory {
            $inventory = $this->lockInventory($inventoryId);
            $this->validateStateTransition($inventory, TransactionType::RETURN, $quantity, InventoryState::SHIPPED);

            $fromState = $this->resolveState($inventory);
            $inventory->shipped_quantity -= $quantity;
            $inventory->available_quantity += $quantity;
            $this->incrementVersion($inventory);
            $inventory->save();

            $this->createLedgerEntry(
                inventory: $inventory,
                transactionType: TransactionType::RETURN,
                fromState: $fromState,
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
     * Transfer inventory between warehouses.
     */
    public function transfer(int $sourceInventoryId, int $destinationInventoryId, int $quantity, string $referenceType, string $referenceId, ?string $performedBy = null, ?string $notes = null): array
    {
        return DB::transaction(function () use ($sourceInventoryId, $destinationInventoryId, $quantity, $referenceType, $referenceId, $performedBy, $notes): array {
            $sourceInventory = $this->lockInventory($sourceInventoryId);
            $destinationInventory = $this->lockInventory($destinationInventoryId);

            if ($sourceInventory->id === $destinationInventory->id) {
                throw new InventoryTransferException('Source and destination inventories must be different.');
            }

            $this->validateAvailableQuantity($sourceInventory, $quantity, TransactionType::TRANSFER_OUT);

            $sourceFromState = $this->resolveState($sourceInventory);
            $destinationFromState = $this->resolveState($destinationInventory);

            $sourceInventory->available_quantity -= $quantity;
            $destinationInventory->available_quantity += $quantity;

            $this->incrementVersion($sourceInventory);
            $this->incrementVersion($destinationInventory);

            $sourceInventory->save();
            $destinationInventory->save();

            $this->createLedgerEntry(
                inventory: $sourceInventory,
                transactionType: TransactionType::TRANSFER_OUT,
                fromState: $sourceFromState,
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
                fromState: $destinationFromState,
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
     * Validate that there is enough available stock for a movement.
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
     * Validate that a state transition is allowed for the given movement.
     */
    private function validateStateTransition(Inventory $inventory, TransactionType $transactionType, int $quantity, InventoryState $expectedState): void
    {
        if ($quantity <= 0) {
            throw new InvalidInventoryStateException(sprintf('Quantity must be greater than zero for %s.', $transactionType->value));
        }

        $currentState = $this->resolveState($inventory);
        if ($currentState !== $expectedState) {
            throw new InvalidInventoryStateException(sprintf('Invalid inventory state for %s. Expected %s but found %s.', $transactionType->value, $expectedState->value, $currentState->value));
        }

        $sourceQuantity = match ($expectedState) {
            InventoryState::RESERVED => $inventory->reserved_quantity,
            InventoryState::PICKED => $inventory->picked_quantity,
            InventoryState::SHIPPED => $inventory->shipped_quantity,
            default => 0,
        };

        if ($sourceQuantity < $quantity) {
            throw new InsufficientInventoryException(sprintf('Insufficient %s quantity for %s.', $expectedState->value, $transactionType->value));
        }
    }

    /**
     * Determine the current inventory state from the snapshot.
     */
    private function resolveState(Inventory $inventory): InventoryState
    {
        if ($inventory->available_quantity > 0) {
            return InventoryState::AVAILABLE;
        }

        if ($inventory->reserved_quantity > 0) {
            return InventoryState::RESERVED;
        }

        if ($inventory->picked_quantity > 0) {
            return InventoryState::PICKED;
        }

        if ($inventory->shipped_quantity > 0) {
            return InventoryState::SHIPPED;
        }

        return InventoryState::AVAILABLE;
    }

    /**
     * Increment inventory version.
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
