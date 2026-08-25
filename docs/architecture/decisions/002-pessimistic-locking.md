# ADR — Pessimistic locking

## Context
Concurrent reservations and transfers must not oversell or deadlock unnecessarily.

## Decision
Use DB transactions, lockForUpdate(), and ascending inventory ID order for transfers.

## Alternatives Considered
Application checks; optimistic locking.

## Why
The database serializes competing changes.

## Trade-offs
Contention and long provider transactions.

## Evidence in Code
app/Services/InventoryService.php; app/Services/ReservationService.php; tests/Feature/InventoryReservationEngineTest.php
