# 8. Idempotency

## Reservations

ReservationService locks the order, searches for ACTIVE or CONSUMED reservations, and returns the existing record. This is order-scoped application idempotency, not a generic request-id framework. The feature test verifies one reservation and one stock movement after a repeated command.

## Webhooks

handleWebhook checks shipment_id plus event_id before inserting ShipmentWebhook. Duplicates return the existing row. The migration additionally makes event_id globally unique. New events persist payload, provider/event/status fields, and processed_at. shipment.delivered invokes confirmShipment.

The signature function accepts missing signatures and only validates a sha256= prefix; it does not calculate an HMAC. This is placeholder validation, not production webhook authentication.

## Shipment processing

A DELIVERED shipment returns unchanged and a provider DUPLICATE response maps to IN_TRANSIT. There is no durable provider idempotency key or dispatch claim. Reprocessing PENDING or FAILED can call the provider again, so full shipment-command idempotency must not be advertised.

Simultaneous webhook inserts rely on the unique database constraint as the final backstop; the existence check itself is not a row lock.
