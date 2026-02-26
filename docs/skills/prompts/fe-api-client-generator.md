# Prompt Template: FE API Client Generator

## Required Inputs
- `api.v1.*` and `action.*` route contracts
- Request/response types

## Expected Outputs
- Typed FE service clients
- Types
- Unit tests

## Constraints
- HTTP calls only from FE service clients
- Unified error schema mapping

## DoD
1. Client methods match routes/contracts.
2. Error mapping tested.

## Stop and Request Approval
- Stop if shared Core HTTP wrapper contract changes are required.
