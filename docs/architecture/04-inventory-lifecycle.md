# 4. Inventory lifecycle

flowchart LR
  A[available] -->|reserve| R[reserved]
  R -->|release| A
  R -->|pick| P[picked]
  P -->|ship| S[shipped]
  S -->|return| A
  A -->|transfer out| D[available at destination]

Implemented formulas in InventoryService:

- reserve: available minus q; reserved plus q
- release: reserved minus q; available plus q
- pick: reserved minus q; picked plus q
- ship: picked minus q; shipped plus q
- return: shipped minus q; available plus q
- transfer: source available minus q; destination available plus q

Each operation requires positive quantity, validates the relevant source bucket, increments version, and creates a ledger entry. A row may have several non-zero buckets simultaneously; currentState() is only a convenience projection and is not used for transition validation.

The movement formulas preserve the sum of buckets during normal operations, but code and migrations do not assert that available + reserved + picked + shipped equals total_quantity. Treat that as a convention, not a formally enforced invariant.
