# Prompt Template: Service Orchestrator Builder

## Required Inputs
- Use-case steps
- Repositories involved

## Expected Outputs
- Service file(s)
- Relevant tests

## Constraints
- Service owns transaction boundaries
- Early returns; avoid deep nesting
- No query builder in controller/service except transaction boundary
- Events represent happened facts

## DoD
1. Happy/unhappy/edge orchestration tests pass.
2. Rollback behavior verified.

## Stop and Request Approval
- Stop if cross-module refactor is required.
