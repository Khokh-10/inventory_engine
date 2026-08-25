# 10. Design patterns and techniques

| Pattern / technique | Status | Evidence | Meaning |
|---|---|---|---|
| Service Layer | Implemented | app/Services/*.php | Workflows are centralized in services. |
| Dependency Injection | Implemented | service constructors and job handle | Laravel container supplies dependencies. |
| Dependency Inversion | Implemented | ShippingProviderInterface and binding | Shipment workflow depends on a contract. |
| Strategy-like provider seam | Partial/style | interface plus MockShippingProvider | Provider implementations can vary; only mock is supplied. |
| Repository Pattern | Not implemented | no repositories in tree | Services use Eloquent directly. |
| State Pattern | Not implemented | enums plus guards | Enums do not encapsulate state behavior. |
| State-based modeling | Implemented | ReservationStatus, ShipmentStatus, InventoryState | Status and guarded operations model lifecycle. |
| Transactions | Implemented | DB::transaction | Local writes are atomic. |
| Pessimistic locking | Implemented | lockForUpdate | Critical rows are serialized. |
| Idempotency | Scoped implemented | reservation and webhook code | Specific duplicate paths are protected. |
| Ledger/audit trail | Implemented | InventoryLedger and ShipmentHistory | Movement/events are persisted; not event sourcing. |
| Queue processing | Implemented | job, command, queue config | Shipment work is async-capable. |
| Retry strategy | Layered | job settings and retryShipment | Queue and business retries are separate. |

Do not label this repository as Clean Architecture, CQRS, event-driven architecture, Repository Pattern, or State Pattern.
