# Warehouse Inventory Reservation Engine — Architecture

Source-led architecture documentation for the Laravel/PHP inventory correctness engine in Khokh-10/inventory_engine (main branch).

## Purpose

The implemented core coordinates warehouse inventory buckets, reservations, shipment processing, provider outcomes, duplicate webhooks, partial shipments, retries, and movement history. Claims in this package are tied to repository files; incomplete behavior is explicitly labeled.

## Architecture at a glance

Caller or test → ReservationService / InventoryService / ShipmentService → Eloquent models → relational database → InventoryLedger

Shipment creation → ProcessShipmentJob → ShipmentService → ShippingProviderInterface → MockShippingProvider

## Scope boundary

The repository currently has only Laravel's default / web route. No domain HTTP controllers or webhook routes are present. Services are the practical application boundary used by tests, jobs, commands, and future controllers.

## Evidence

- Services: app/Services/InventoryService.php, ReservationService.php, ShipmentService.php
- Async: app/Jobs/ProcessShipmentJob.php and app/Console/Commands/ProcessPendingShipmentsCommand.php
- Provider seam: app/Contracts/ShippingProviderInterface.php, app/Services/Providers/MockShippingProvider.php, app/Providers/AppServiceProvider.php
- Persistence: app/Models/ and database/migrations/
- Tests: tests/Feature/InventoryReservationEngineTest.php

Read the numbered documents for flows, constraints, patterns, decisions, and limitations.
