# 15. Trade-offs and limitations

- Direct Eloquent use keeps this Laravel project simple; introduce repositories only for a demonstrated boundary.
- Pessimistic locks make correctness clear; measure contention before scaling and consider partitioned workloads if needed.
- version increments as a change counter, not optimistic locking; add compare-and-swap only if required.
- MockShippingProvider simulates outcomes; replace it with real adapters, durable provider idempotency, and contract tests.
- Webhook signatures only check a prefix and allow absent signatures; implement provider-specific HMAC over the raw body.
- No domain controllers or webhook routes exist; add authenticated request validation and authorization before exposing this over HTTP.
- Expiration is callable but not scheduled; add a scheduled command/job for due active reservations.
- Provider calls inside transactions can hold locks and cannot roll back external effects; use an outbox/claim protocol in production.
- Snapshot buckets plus ledger are efficient but not replayable event sourcing; add reconciliation and DB checks.
- reservations.order_id lacks uniqueness, and event_id is globally rather than provider-scoped; choose constraints deliberately for multi-provider production.
- Enum states exceed implemented transitions; do not advertise LABEL_CREATED, PACKED, RELEASED, or adjustment workflows as supported without code/tests.
