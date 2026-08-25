# Architecture presentation

## Slide 1 — Warehouse Inventory Reservation Engine
Laravel/PHP backend focused on correctness under warehouse concurrency.

## Slide 2 — Problem
Maintain stock while reservations, picking, shipment, retries, returns, transfers, and duplicate external messages overlap.

## Slide 3 — Requirements
Atomicity, row locking, scoped idempotency, partial quantity accounting, async provider work, and auditability.

## Slide 4 — Architecture
Caller → services → Eloquent → relational DB; shipment → queue → ProcessShipmentJob → provider contract → mock provider.

## Slide 5 — Domain
Order → Reservation → ReservationItem → Inventory; Order → Shipment → ShipmentItem; shipment webhooks/history and inventory ledger.

## Slide 6 — Inventory
available → reserved → picked → shipped; release and return move back to available; transfer moves available between rows.

## Slide 7 — Reservation
Lock order → find existing non-terminal reservation → reserve locked inventory → create reservation items and ledger.

## Slide 8 — Concurrency problem
Two orders request one unit; without serialization both can read the same available value.

## Slide 9 — Solution
DB transactions, lockForUpdate(), and ascending transfer lock order.

## Slide 10 — Idempotency
Order-scoped reservation reuse and durable webhook event reuse; no full provider-call idempotency.

## Slide 11 — Shipment
Queue job, typed provider outcomes, history, partial shipment, delivery confirmation, and bounded retry metadata.

## Slide 12 — Ledger
Movement type, source/destination labels, quantity, reference, actor, notes, timestamp; audit trail, not event sourcing.

## Slide 13 — Patterns
Service layer, DI, contract inversion, transactions, pessimistic locking, scoped idempotency, queue processing. No Repository/State/CQRS claim.

## Slide 14 — Tests
Six feature scenarios: oversell, reservation duplicate, mixed buckets, webhook duplicate, partial shipment, insufficient stock.

## Slide 15 — Production
Real providers, HMAC, routes/auth, scheduled expiration, unified retries, outbox, and stronger constraints.

## Slide 16 — Lessons
Correctness over CRUD; explicit critical sections; atomic local writes; bounded partials; auditability; honest boundaries.
