# Prompt Template: Redis Keyspace & Idempotency Designer

## Required Inputs
- Module/entity/action
- TTL policy

## Expected Outputs
- Redis key constants/enums
- Idempotency helper/service
- Tests

## Constraints
- Key format `{module}:{entity}:{purpose}:{id}`
- TTL mandatory for cache/idempotency
- Dedupe check before side effects

## DoD
1. Duplicate replay returns stable result.
2. No duplicate side effects.

## Stop and Request Approval
- Stop if keyspace change impacts out-of-scope modules.
