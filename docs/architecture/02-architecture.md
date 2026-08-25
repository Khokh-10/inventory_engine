# 2. Architecture

## Actual style

Laravel MVC plus application services, Eloquent Active Record models, relational transactions, queue jobs, a console dispatch command, and one provider interface. This is not claimed as Clean Architecture or Hexagonal Architecture: no domain repository ports or separate domain entity layer exist.

flowchart LR
  Caller[Tests / future caller] --> RS[ReservationService]
  RS --> IS[InventoryService]
  Job[ProcessShipmentJob] --> SS[ShipmentService]
  Command[shipments:process] --> Job
  SS --> SPI[ShippingProviderInterface]
  SPI --> Mock[MockShippingProvider]
  RS --> E[Eloquent models]
  IS --> E
  SS --> E
  E --> DB[(Relational database)]
  IS --> Ledger[InventoryLedger]

InventoryService owns bucket movements and ledger writes. ReservationService owns reservation orchestration and delegates stock changes. ShipmentService owns shipment state, provider mapping, webhook persistence, history, and shipment item accounting. Jobs resolve services through Laravel's container. AppServiceProvider binds ShippingProviderInterface to MockShippingProvider.
