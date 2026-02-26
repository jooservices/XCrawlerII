# Prompt Template: Route Group & Controller Scaffold

## Required Inputs
- Module name
- Group name
- Endpoint list for render/action/api

## Expected Outputs
- `Modules/<Module>/routes/web.php`
- `Modules/<Module>/routes/api_v1.php`
- `Modules/<Module>/app/Http/Controllers/<Group>Controller.php`
- `Modules/<Module>/app/Http/Controllers/Api<Group>Controller.php`

## Constraints
- `web.php` contains BOTH `render.<group>.*` and `action.<group>.*`
- `api_v1.php` contains `api.v1.<group>.*`
- `<Group>Controller`: `renderX/actionX`
- `Api<Group>Controller`: REST/action methods

## DoD
1. Route names/prefixes match standards.
2. No scattered web route files.

## Stop and Request Approval
- Stop if route changes require out-of-scope module changes.
