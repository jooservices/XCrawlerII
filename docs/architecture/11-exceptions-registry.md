# 11 - Exceptions Registry

## Exception Policy
Rule `11-EXC-001`:
Any rule exception MUST be approved, recorded, time-boxed, and owned before implementation.

Rationale:
Uncontrolled exceptions become permanent architecture debt.

Allowed:
```md
Exception ID: EX-2026-003
Rule Ref: 02-ROU-005
Status: Approved
Owner: team-auth
Expiry: 2026-05-31
```

Forbidden:
```md
TODO temporary bypass
```

Verification:
- Every exception includes owner, expiry, rollback plan, and approval.

## Exception Registry Template
```md
## EX-YYYY-NNN - <short title>
- Rule Ref: <e.g., 03-BE-004>
- Category: <ROUTING|LAYERING|DTO|TEST|QUALITY|HARDCODE|OTHER>
- Requested By: <team/person>
- Owner: <team/person>
- Date Requested: YYYY-MM-DD
- Date Approved: YYYY-MM-DD
- Approver: <name/role>
- Scope: <module/files/routes>
- Justification: <1-3 concise bullets>
- Risk: <impact if kept>
- Mitigation: <controls while exception active>
- Expiry Date: YYYY-MM-DD
- Rollback Plan: <how to remove exception>
- Tracking Ticket: <id/url>
- Status: <Proposed|Approved|Expired|Rejected|Closed>
```

## Active Exceptions
| ID | Rule Ref | Scope | Owner | Expiry | Status |
|---|---|---|---|---|---|
| EX-2026-001 | 05-API-007 | `/api/v1/auth/*` | team-auth | 2026-12-31 | Approved |

## Refactor Request (Approval Required)
Rule `11-EXC-002`:
Any out-of-scope refactor required to unblock feature delivery MUST use this template and wait for explicit approval.

Rationale:
Prevents drive-by refactors hidden in feature delivery.

Allowed:
```md
## Refactor Request: RR-2026-004
- Requested By: <name>
- Feature Link: <spec/ticket>
- Why Needed: <blocking issue>
- Scope Files: <absolute paths or globs>
- Risk if Not Done: <brief>
- Proposed Changes: <brief>
- Test Impact: <what needs rerun>
- Approval: <Pending/Approved/Rejected + approver>
```

Forbidden:
```md
Refactored unrelated module while implementing feature.
```

Verification:
- PR includes approved RR ID for unrelated refactor changes.

## Expiry Enforcement
Rule `11-EXC-003`:
Expired exceptions are policy violations until renewed or removed. A scheduled CI job/cron MUST scan for expired exceptions and block or warn according to repository policy.

Rationale:
Time-boxing only works if expiration is enforced.

Allowed:
```md
Status changed to Expired; removal task created immediately.
```

Forbidden:
```md
Keep using expired exception silently.
```

Verification:
- Weekly review checks expiry dates and status updates.
- Scheduled CI/cron report identifies expired entries and gate action (block/warn).
