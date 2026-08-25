# ADR — Queue-based shipment processing

## Context
Provider work can be slow or fail separately from callers.

## Decision
Use ProcessShipmentJob with attempts, backoff, timeout, and failed hook.

## Alternatives Considered
Synchronous-only; unbounded retries.

## Why
Separates provider work and records terminal failure.

## Trade-offs
No outbox; job and business retries are separate.

## Evidence in Code
app/Jobs/ProcessShipmentJob.php; app/Console/Commands/ProcessPendingShipmentsCommand.php
