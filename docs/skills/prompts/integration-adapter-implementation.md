# Prompt Template: Integration Adapter Implementation

## Input
- External API contract
- Retry/timeout requirements

## Required Output
- Core port interface
- Adapter using `jooservices/client`
- Boundary DTOs
- Adapter tests

## Constraints
- Domain uses interface only
- No direct client in domain service
- DTO boundary-only

## DoD
1. Adapter isolates vendor client details.
2. Failure mapping tested.
3. External schema mapped to internal contracts.
