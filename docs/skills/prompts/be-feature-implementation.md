# Prompt Template: BE Feature Implementation

## Input
- Feature spec path
- Target module
- Route requirements (`render/action/api.v1`)
- Data changes

## Required Output
- Scoped BE file diffs
- Backend tests (Feature/Unit/FormRequest)
- DoD evidence checklist

## Constraints
- Controller -> FormRequest -> Service -> Repository -> Model
- Thin controller, no DB query in controller
- 1 Model <-> 1 Repository
- Zero hardcode
- Reuse-first + why-new-class criterion

## DoD
1. Routes grouped and named correctly.
2. Mandatory test categories present.
3. Quality tools pass.
4. Observability hooks present for mutations.
