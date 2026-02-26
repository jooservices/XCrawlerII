# Skills Playbook

## Purpose
This folder defines reusable AI execution skills for backend, frontend, and cross-cutting implementation.

## Scope
- Backend feature delivery for module architecture.
- Frontend feature delivery for Vue 3 + Inertia.
- API contracts, integrations, testing, quality, and production-readiness reviews.

## Governance
Rule `SKL-001`:
Skills MUST follow `docs/architecture/*` and cannot override architecture policies.

Rationale:
Skills are execution guides, not policy replacements.

Allowed:
```md
Skill output references architecture rule IDs.
```

Forbidden:
```md
Skill bypasses controller-thin policy.
```

Verification:
- Skill outputs include rule references and DoD evidence.
