# Prompt Template: FormRequest Author + Authorization Wiring

## Required Inputs
- Payload contract
- Authorization model

## Expected Outputs
- `Http/Requests/<Action>Request.php`
- Policy/gate wiring
- FormRequest tests

## Constraints
- Implement `authorize()` and `rules()` explicitly
- Use `prepareForValidation()` when needed
- No inline controller validation for same scope

## DoD
1. Authorization allow/deny tests exist.
2. Validation unhappy/edge tests exist.

## Stop and Request Approval
- Stop if policy changes exceed feature scope.
