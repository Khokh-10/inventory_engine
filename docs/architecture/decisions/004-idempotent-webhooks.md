# ADR — Idempotent webhooks

## Context
External providers may redeliver events.

## Decision
Check/persist event ID and enforce unique event_id.

## Alternatives Considered
Process every delivery; cache-only deduplication.

## Why
Durable duplicate handling.

## Trade-offs
Prefix-only signature check; global event scope.

## Evidence in Code
app/Services/ShipmentService.php; shipment webhooks migration
