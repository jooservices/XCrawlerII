# Prompt Template: Repository Query Designer

## Required Inputs
- Model
- Filter/sort/pagination contract

## Expected Outputs
- Repository implementation
- Query tests

## Constraints
- Repository is query owner
- Eager loading for N+1 prevention
- No DB query in service/controller

## DoD
1. Filters/sort/pagination validated.
2. N+1 risk checked.

## Stop and Request Approval
- Stop if shared repository refactor is required.
