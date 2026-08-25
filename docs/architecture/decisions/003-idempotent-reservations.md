# ADR — Idempotent reservations

## Context
Retries may repeat an order reservation command.

## Decision
Lock order and return existing ACTIVE/CONSUMED reservation.

## Alternatives Considered
Request IDs; unique order_id only.

## Why
Prevents duplicate reservation movement in the implemented flow.

## Trade-offs
No order_id unique constraint; limited terminal semantics.

## Evidence in Code
app/Services/ReservationService.php; reservations migration
