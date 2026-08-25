# 7. Concurrency control

sequenceDiagram
  participant A as Reservation A
  participant B as Reservation B
  participant I as Inventory row
  A->>I: lockForUpdate
  B->>I: lockForUpdate waits
  A->>I: validate 1 and reserve 1
  A-->>I: commit
  B->>I: validate 0
  I-->>B: InsufficientInventoryException

InventoryService wraps movements in DB::transaction and obtains inventory with lockForUpdate(). ReservationService locks the order before duplicate detection, preventing two same-order calls from both creating a reservation. ShipmentService locks shipment rows for processing, confirmation, failure, and retry.

Transfers sort the two inventory IDs ascending before locking them. Opposite-direction transfers therefore acquire locks in the same order, reducing circular wait risk.

Inventory version is incremented, but no compare-and-swap predicate or optimistic conflict exception exists. Pessimistic locking is the actual correctness mechanism.

Caveat: processShipment calls the external provider inside its transaction, so a database lock can remain held during external work. An outbox or claim protocol would shorten that boundary.
