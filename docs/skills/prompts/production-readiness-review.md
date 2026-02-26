# Prompt Template: Production Readiness Review

## Input
- Feature diff
- DoD status
- Tool/test outputs
- Exceptions

## Required Output
- PASS/FAIL per DoD item
- Severity-ranked findings
- Remediation list

## Constraints
- Evidence-driven only
- Verify observability hooks
- Verify exception validity/expiry

## DoD
1. Findings include file/rule references.
2. No unresolved critical/high issues for PASS.
3. Exception ownership/expiry validated.
