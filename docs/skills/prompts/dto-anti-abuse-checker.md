# Prompt Template: DTO Anti-Abuse Checker

## Required Inputs
- Feature scope
- Proposed DTO list
- Boundary map

## Expected Outputs
- DTO decision report
- Only justified DTO files

## Constraints
- Budget 2-4 per feature (including Core-placed DTOs)
- DTO must provide boundary contract value
- No internal method-to-method DTO passing

## DoD
1. Each DTO has boundary + value justification.
2. Budget respected or exception approved.

## Stop and Request Approval
- Stop if budget exceeded without approved exception.
