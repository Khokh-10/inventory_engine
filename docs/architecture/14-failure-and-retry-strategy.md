# 14. Failure and retry strategy

| Scenario | Behavior | Classification |
|---|---|---|
| Insufficient stock | InsufficientInventoryException; transaction rolls back | Terminal business failure |
| Invalid quantity/state | Domain exception; local writes roll back | Validation failure |
| Duplicate reservation | Return existing ACTIVE/CONSUMED reservation | Handled duplicate |
| Duplicate webhook | Return stored webhook | Handled duplicate |
| Provider FAILED | Shipment becomes FAILED | Terminal provider outcome unless explicitly retried |
| Provider TIMEOUT | Shipment remains PENDING | Retriable outcome |
| Partial success | Ship bounded partial quantity; status PARTIALLY_DELIVERED | Partial outcome |
| Job exception | Laravel retries using job properties | Retriable infrastructure failure |
| Exhausted job | failed() attempts FAILED transition and logs | Terminal job failure |
| Explicit business retry | retryShipment permits three attempts, then FAILED | Bounded retry |
| Reservation expiration | Explicit method releases stock | No automatic trigger found |

The job's five queue attempts do not call retryShipment(), so infrastructure retry count and business retry_count are not one unified policy. Provider calls occur inside a DB transaction; external side effects cannot be rolled back if the local transaction fails.
