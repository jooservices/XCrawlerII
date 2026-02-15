# Troubleshooting / FAQ

## Phase 3 Clarity Review Summary

The documentation set was reviewed in 3 simulated junior/fresher passes.

### Iteration 1 Questions
- "Where do I start: business docs or coding docs?"
- "What is the exact command order before commit?"

Resolution:
- Added reading order to `docs/README.md`.
- Added explicit command sequence in `guides/getting-started.md` and `guides/implementation-guide.md`.

### Iteration 2 Questions
- "How does an authenticated request move through middleware and services?"
- "Which endpoints are admin-only?"

Resolution:
- Added full request lifecycle sequence in `architecture/request-lifecycle.md`.
- Added grouped endpoint sections in `api/api-reference.md`.

### Iteration 3 Questions
- "How do I write new code without breaking architecture style?"
- "What should be mocked in tests?"

Resolution:
- Added code skeleton patterns under `guides/code-skeletons/`.
- Added mock/data guidance in `testing/testing-strategy.md`.

No remaining unresolved clarity questions.

## Common Pitfalls

1. **Quality command fails after new rule changes**
   - Run `composer format` then `composer quality`.
   - If PHPStan fails on known legacy issues, refresh baseline intentionally and review diff.

2. **Pre-push blocks push unexpectedly**
   - Hook runs `composer test`.
   - Fix failing tests locally; do not bypass by default.

3. **Sync jobs dispatched but no data appears**
   - Check queue worker/Horizon status.
   - Check admin sync and telemetry pages for failures/timeouts.

4. **Search results are stale**
   - Validate Elasticsearch connectivity and index sync job completion.

## Error-to-Action Quick Table

- `401 Unauthorized` → login/session issue; authenticate first.
- `403 Forbidden` → role/permission mismatch; verify account role.
- `422 Validation` → inspect field-level errors and request payload.
- `500 Internal Server Error` → inspect logs and telemetry for root cause.
