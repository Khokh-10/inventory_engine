# 9. Data consistency

Inventory updates, version increments, and ledger entries share a transaction. Reservation creation, cancellation, expiration, and consumption use transactions and delegate bucket movements. Shipment creation, processing, confirmation, retry, failure, and webhook handling also use service transactions.

Service-level rules include positive quantities, non-negative source buckets, ACTIVE-only reservation operations, CONSUMED-only shipment creation, delivered no-op behavior, and recomputation of shipment remaining_quantity from original minus shipped quantity.

Foreign keys and unique constraints support local consistency: unique customer email, product SKU, warehouse code, warehouse/product inventory identity, and webhook event ID. Statuses are strings cast to enums.

No database CHECK constraints enforce bucket sums, transition graphs, or shipment item arithmetic. total_quantity is not recomputed. Ledger rows are not database-immutable. Cross-table invariants rely on service code and transaction discipline.
