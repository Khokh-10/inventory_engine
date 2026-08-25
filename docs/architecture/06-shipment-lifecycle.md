# 6. Shipment lifecycle

stateDiagram-v2
  [*] --> PENDING : consumed reservation
  PENDING --> IN_TRANSIT : provider SUCCESS
  PENDING --> PARTIALLY_DELIVERED : PARTIAL_SUCCESS
  PENDING --> PENDING : TIMEOUT
  PENDING --> FAILED : FAILED / exhausted job
  FAILED --> PENDING : explicit retry under limit
  IN_TRANSIT --> DELIVERED : confirm or delivered webhook
  PARTIALLY_DELIVERED --> DELIVERED : confirm

createShipment requires CONSUMED, creates PENDING and item counters, and records history. processShipment locks the shipment, calls the provider contract, persists provider data, and maps ProviderResponse to status/history. partialShipment ships no more than remaining quantity. confirmShipment ships all remaining picked quantities and marks DELIVERED; repeated confirmation is a no-op.

ShipmentStatus also declares LABEL_CREATED, PICKED, PACKED, and CANCELLED, but no service transitions into these states were found.
