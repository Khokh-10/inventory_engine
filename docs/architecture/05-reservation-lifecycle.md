# 5. Reservation lifecycle

stateDiagram-v2
  [*] --> ACTIVE : createReservation
  ACTIVE --> CONSUMED : consumeReservation
  ACTIVE --> CANCELLED : cancelReservation
  ACTIVE --> EXPIRED : expireReservation
  PENDING --> ACTIVE : enum/schema vocabulary
  ACTIVE --> RELEASED : enum value only; no service transition found

createReservation runs in a transaction, locks the order, and searches for an ACTIVE or CONSUMED reservation. If found, it returns that reservation. Otherwise it creates ACTIVE with expires_at one day ahead, reserves each inventory item, and creates reservation items.

Cancellation and expiration require ACTIVE, release every reserved quantity through InventoryService, then set CANCELLED or EXPIRED. Consumption requires ACTIVE and changes only reservation status to CONSUMED; picking is a separate inventory operation.

ReservationStatus includes PENDING and RELEASED, but the inspected service does not transition to them. No scheduled expiration trigger was found. Same-order duplicate protection depends on the order lock and application check; reservations.order_id has no unique database constraint.
