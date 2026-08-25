# ADR — Provider abstraction

## Context
Shipment orchestration should not hard-code one carrier.

## Decision
Depend on ShippingProviderInterface and bind MockShippingProvider.

## Alternatives Considered
Hard-coded carrier logic.

## Why
Alternate implementations and test doubles fit the seam.

## Trade-offs
Only a random mock provider is supplied.

## Evidence in Code
app/Contracts/ShippingProviderInterface.php; app/Providers/AppServiceProvider.php
