# Prompt Template: Quality Tool Fixer

## Required Inputs
- Pint/PHPCS/PHPStan/PHPMD outputs
- Allowed scope

## Expected Outputs
- Scoped fixes only
- Tool run evidence notes

## Constraints
- Fix order: Pint -> PHPCS -> PHPStan/Larastan -> PHPMD
- No unrelated refactor

## DoD
1. Required tools pass for scoped files.
2. No out-of-scope edits.

## Stop and Request Approval
- Stop if fix requires broad architectural refactor.
