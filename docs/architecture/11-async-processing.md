# 11. Async processing

sequenceDiagram
  participant C as Console command
  participant Q as Laravel queue
  participant J as ProcessShipmentJob
  participant S as ShipmentService
  participant P as Provider
  C->>Q: dispatch shipment ID
  Q->>J: execute
  J->>S: processShipment
  S->>P: ship
  P-->>S: ProviderResponse
  S-->>Q: commit or throw

ProcessPendingShipmentsCommand counts pending shipments, reads them with chunkById(100), and dispatches one ProcessShipmentJob per shipment. The job stores only shipment ID and resolves ShipmentService and ShippingProviderInterface in handle().

Job settings: tries 5, backoff 10/30/60/120/300 seconds, timeout 120 seconds, retryUntil 30 minutes. failed() attempts to mark the shipment FAILED and logs errors.

The command is not registered as a scheduler in the inspected source. A running worker/deployment topology is not documented by the repository. Job exception retries are separate from ShipmentService::retryShipment(), which maintains a three-attempt business counter.
