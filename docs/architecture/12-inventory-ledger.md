# 12. Inventory ledger

InventoryLedger is an append-style movement record created by InventoryService in the same transaction as each snapshot update. Fields include transaction type, from/to state labels, quantity, reference type/id, performed_by, notes, and created_at.

TransactionType defines INITIAL_INTAKE, RESERVE, RELEASE, PICK, SHIP, RETURN, TRANSFER_IN, TRANSFER_OUT, ADJUSTMENT_IN, and ADJUSTMENT_OUT. The inspected service emits reserve, release, pick, ship, return, and transfer in/out. Adjustment values are vocabulary/schema support, not observed service methods.

The ledger provides a chronological explanation for reconciliation and debugging while snapshots keep reads efficient. It is not event sourcing: inventory is not rebuilt by replaying it, and the database does not enforce immutability or uniqueness of movements. Indexes cover inventory_id, transaction_type, and the reference pair.
