# ADR — Inventory ledger

## Context
Mutable buckets need an explanation of how they changed.

## Decision
Append a ledger row in the same transaction as every movement.

## Alternatives Considered
No history; rebuild everything from events.

## Why
Auditability with efficient snapshot reads.

## Trade-offs
Not replayable event sourcing; no DB immutability.

## Evidence in Code
app/Services/InventoryService.php; app/Models/InventoryLedger.php; inventory ledgers migration
