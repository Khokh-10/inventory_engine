# ADR — Service layer

## Context
Coordinate model writes, locks, and invariants in one place.

## Decision
Keep inventory, reservation, and shipment workflows in dedicated services.

## Alternatives Considered
Controller-only logic; repositories as mandatory abstraction.

## Why
Critical sections remain visible and Laravel-native.

## Trade-offs
Services are coupled to Eloquent.

## Evidence in Code
app/Services/InventoryService.php; app/Services/ReservationService.php; app/Services/ShipmentService.php
