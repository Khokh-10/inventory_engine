# Warehouse Inventory Reservation Engine

Core inventory correctness engine for a multi-warehouse ERP: safe reservations,
shipment processing with a mock shipping provider, and full movement history —
built to remain correct under concurrent access, retries, and duplicate events.

See [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) for the domain model, lifecycle,
and concurrency design, and [`docs/AI_USAGE.md`](docs/AI_USAGE.md) for AI usage
transparency.

## Requirements
- PHP 8.2+
- MySQL 8+
- Composer

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure your database in `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_engine
DB_USERNAME=root
DB_PASSWORD=
```

Run migrations (and seeders, if present):
```bash
php artisan migrate --seed
```

## Running the app / queue

```bash
php artisan serve
```

Shipments are processed asynchronously — run a queue worker in a separate terminal:
```bash
php artisan queue:work
```

To dispatch pending shipments for processing:
```bash
php artisan shipments:process
```
(Artisan command name may differ — check `app/Console/Commands` for the exact
signature registered in this repo.)

## Running tests

```bash
php artisan test
```
or, if using Pest:
```bash
./vendor/bin/pest
```

Tests cover:
- Concurrent reservation of the last available unit (no oversell)
- Idempotent reservation creation (same command run twice)
- Duplicate shipment webhook handling
- Partial shipment inventory correctness
- Insufficient-stock rejection
- Correct release/pick behavior when multiple inventory buckets are non-zero
  simultaneously (regression test for a state-validation bug found during
  development — see `docs/AI_USAGE.md`)

### Evidence — passing test run

```
PS E:\backend(php)\backend projects\ERB\inventory-engine> php artisan test --filter=InventoryReservationEngineTest

  PASS  Tests\Feature\InventoryReservationEngineTest
  ✓ it prevents overselling when two reservations race for the last unit
  ✓ it is idempotent when the same reservation command runs twice for the same order
  ✓ it allows release and pick to succeed when both available and reserved buckets are non-zero
  ✓ it ignores a duplicate shipment webhook for the same event id
  ✓ it ships only the remaining quantity on a partial shipment, not the full amount again
  ✓ it rejects a reservation when requested quantity exceeds available stock

  Tests:    6 passed (19 assertions)
  Duration: 0.67s
```

![Passing tests](docs/evidence/tests-passing.png)

## Key assumptions
- A reservation expires 24 hours after creation (`expires_at = now()->addDay()`),
  released via a scheduled scan calling `ReservationService::expireReservation()`,
  not an automatic DB-level TTL.
- One active (ACTIVE or CONSUMED) reservation is allowed per order at a time;
  re-running the reservation command for an order that already has one returns the
  existing reservation instead of creating a duplicate.
- Partial shipments ship as much of the *remaining* (not total) item quantity as the
  mock provider reports, and leave the shipment in `PARTIALLY_DELIVERED` until a
  follow-up confirmation completes it.
- Inventory transfers between warehouses only move `available_quantity`; reserved,
  picked, and shipped quantities are never affected by a transfer.
- `performed_by` on ledger/history entries defaults to `'system'` where no
  authenticated actor is available — authentication/authorization is out of scope
  for this engine per the brief.

## Known limitations
- No authentication/authorization layer — service methods assume a trusted caller
  (controller/job) already authorized the action.
- Webhook signature verification is a placeholder (`verifyWebhookSignature`) and
  needs a real HMAC check against a provider secret before production use.
- Reservation expiry requires a scheduled command to be registered in
  `routes/console.php` / the scheduler — it is not automatic.
- Single-database design; see `docs/ARCHITECTURE.md §6` for the scaling path if
  transaction volume grows beyond a single MySQL primary.