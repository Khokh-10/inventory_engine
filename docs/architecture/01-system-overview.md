# 1. System overview

This is a Laravel MVC application with a prominent service layer. Its correctness-critical behavior is implemented in three services and persisted through Eloquent.

## Implemented components

| Component | Responsibility | Evidence |
|---|---|---|
| InventoryService | Atomic reserve, release, pick, ship, return, transfer and ledger writes | app/Services/InventoryService.php |
| ReservationService | Order reservation creation, duplicate return, cancel, expire, consume | app/Services/ReservationService.php |
| ShipmentService | Shipment creation, provider outcomes, partial/delivery handling, retries, webhooks | app/Services/ShipmentService.php |
| ProcessShipmentJob | Queue boundary and terminal failure handling | app/Jobs/ProcessShipmentJob.php |
| Provider contract/mock | Provider abstraction and simulated outcomes | app/Contracts/ShippingProviderInterface.php; app/Services/Providers/MockShippingProvider.php |
| Eloquent/migrations | Relational persistence and relationships | app/Models/; database/migrations/ |

## Main data flow

ReservationService locks the order, detects an existing ACTIVE or CONSUMED reservation, or creates one and delegates each stock movement to InventoryService. InventoryService locks inventory, validates the source bucket, updates quantities, increments version, and writes a ledger row in one transaction.

A consumed reservation becomes a shipment. Shipment processing runs through a queue job and provider contract. Provider responses update shipment status and history. A delivered webhook invokes delivery confirmation, which ships remaining picked inventory.

There are no domain controllers or HTTP endpoints for these workflows in routes/.
