# Prompt Template: API Contract Design

## Input
- Resource operations
- Auth/security requirements
- Error scenarios

## Required Output
- `routes/api_v1.php` updates
- FormRequest rules
- Resource/DTO contracts
- API tests

## Constraints
- `/api/v1` + `api.v1.*`
- RESTful resources
- Unified error schema
- Status code mapping enforcement

## DoD
1. Contract tests cover happy/unhappy/security/edge.
2. Response and error schemas validated.
3. Status codes match required table.
