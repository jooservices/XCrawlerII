# Jira Clean Ticket Generator (Prompt)

## Purpose

Generate Jira tickets (Epic, Stories, Tasks, Subtasks) that are **clean for all roles**: Business/Stakeholders, BA, PO/PM, SA, Dev, and QA. Output MUST be paste-ready for Jira and structured so that a junior developer can implement with minimal questions and stakeholders can read without technical noise.

**Governance:** This prompt aligns with [15 - Jira AI Workflow and Approval Gates](../../architecture/15-jira-ai-workflow-and-approval-gates.md). Tickets MUST be created in DRAFT or PENDING_APPROVAL; no implementation before Owner approval.

---

## When to Use

- Owner (or delegate) has a feature idea and wants structured Epic + Stories + Tasks.
- You need a single, reusable prompt to produce role-based, paste-ready ticket content.

---

## Minimal Inputs (Collect These First)

Ask the requester for:

1. **Feature idea** — Short name and 2–4 sentence description (business language).
2. **Constraints** — Technical or business limits (e.g. "no new DB tables", "must work offline", "deadline Q2").
3. **Stakeholders** — Who cares (PO, ops, external partner).
4. **Deadline** (optional) — Target date or milestone.
5. **Out-of-scope** (optional) — Explicit exclusions.

If any of the above is missing, capture it as **Open Questions** in the generated tickets.

---

## Instructions for the AI

You are generating Jira tickets for the XCrawler project. Follow the rules below strictly.

### 1. Hierarchy

- Produce **exactly one Epic** and **one or more Stories**.
- Each Story MUST have **at least 3 Tasks or Subtasks** that are dev-ready (clear steps, files, DoD).
- Use the role-based structure from [15 - Jira AI Workflow and Approval Gates](../../architecture/15-jira-ai-workflow-and-approval-gates.md) for Epic, Story, and Task/Subtask.

### 2. Epic Structure (use headings A–J)

- **A. Business / Stakeholder Summary** — Non-technical; 2–4 sentences.
- **B. Problem Statement + Goal**
- **C. Scope / Out of Scope**
- **D. Personas / Users impacted**
- **E. Success Metrics / KPIs** (if applicable)
- **F. Risks** (business & delivery)
- **G. Dependencies**
- **H. Milestones** — List of Stories (titles or keys)
- **I. Open Questions + Assumptions** — Every assumption explicit and testable; every ambiguity as an open question.
- **J. Reference links** — Internal docs only (e.g. `docs/architecture/05-api-contracts-restful.md`).

### 3. Story Structure (use headings 1–9 in order)

1. **Business (for PO/BA)** — User Story (As/I want/So that), Background, Business Rules (numbered), Edge Cases (business), Roles & Permissions, UX/i18n, Analytics if any.
2. **Acceptance Criteria** — Minimum 8 bullets: ≥3 happy, ≥3 unhappy/validation/permission, ≥1 edge/weird, ≥1 security/abuse if relevant. Use Given/When/Then where helpful.
3. **Non-Functional Requirements** — Performance, Reliability, Observability, Security as applicable.
4. **Technical (for SA/Dev)** — Architecture notes, data model changes, API contract (endpoint, method, auth, request/response, errors), domain objects/DTOs/enums, sequence/flow (optional Mermaid), skeleton (file paths, class names, method signatures), backward compatibility.
5. **Tasks Breakdown** — List of Tasks/Subtasks with clear DoD.
6. **Testing Requirements** — Feature tests, unit tests, security/exploit tests, test data (factories), coverage expectations if any.
7. **Definition of Done** — Reference `docs/architecture/09-feature-definition-of-done.md` + story-specific DoD.
8. **Rollout / Release Plan** — Feature flags, migration order, rollback plan if needed.
9. **Open Questions + Assumptions**

### 4. Task/Subtask Structure

- **Goal** — One sentence.
- **Implementation Steps** — Numbered, low ambiguity.
- **Files to touch** — Paths.
- **Code Skeleton** — Classes/method signatures.
- **Edge cases to handle**
- **Tests to add/update**
- **DoD checklist**

### 5. Quality Rules

- **MUST:** Separate Business vs Technical sections clearly; business content first, technical in dedicated section.
- **MUST:** Put any missing or uncertain information under **Open Questions** or **Assumptions** (assumptions must be testable).
- **MUST NOT:** Allow implementation before Owner approval; state in Epic/Story that tickets start in DRAFT or PENDING_APPROVAL.
- **MUST:** Write so a junior developer can implement with minimal clarification; ambiguity is a defect.
- **MUST:** Align with project architecture: thin controllers, 1 Model ↔ 1 Repository, services own logic, enums/const for domain values (see `docs/architecture/03-backend-architecture-rules.md`, `09-feature-definition-of-done.md`).

### 6. Output Format

- Produce output in **paste-ready** form for Jira:
    - Use clear markdown headings that match Jira’s supported formatting.
    - One block per ticket type: first the Epic (copy-paste body), then each Story (copy-paste body), then each Task/Subtask (copy-paste body).
- At the **end** of the entire output, append a **Quality Checklist** (self-audit) so the requester can verify before pasting.

---

## Quality Checklist (Self-Audit)

Include this checklist at the end of the generated output. Fill with [x] or [ ] as applicable.

```markdown
## Quality Checklist (before pasting to Jira)

- [ ] Epic has all sections A–J; Business Summary is non-technical.
- [ ] Each Story has all sections 1–9 in order.
- [ ] Each Story has ≥8 Acceptance Criteria (≥3 happy, ≥3 unhappy, ≥1 edge, ≥1 security if relevant).
- [ ] Technical section includes API contract, data model, skeleton (files/classes/methods).
- [ ] Each Story has ≥3 Tasks/Subtasks with Goal, Steps, Files, Skeleton, Tests, DoD.
- [ ] Open Questions and Assumptions are listed; no ambiguity left unstated.
- [ ] No implementation implied before Owner approval; workflow is DRAFT/PENDING_APPROVAL first.
- [ ] References to internal docs (e.g. 09-feature-definition-of-done, 03-backend-architecture-rules) where relevant.
- [ ] Junior dev can implement with minimal questions; business stakeholder can read without technical noise.
```

---

## Example Invocation (for the user)

**User:**  
Use the Jira Clean Ticket Generator. Feature: "Export crawl results to CSV for a single job. Constraints: max 50k rows, only job owner or admin. Stakeholders: PO, BA. Deadline: none. Out of scope: bulk export, Excel, scheduled export."

**AI:**  
[Produces one Epic, one Story, three Tasks following the structure above, then the Quality Checklist.]

---

## Reference

- Canonical workflow and templates: [15 - Jira AI Workflow and Approval Gates](../../architecture/15-jira-ai-workflow-and-approval-gates.md)
- DoD: [09 - Feature Definition of Done](../../architecture/09-feature-definition-of-done.md)
- Backend rules: [03 - Backend Architecture Rules](../../architecture/03-backend-architecture-rules.md)
- Testing: [07 - Testing Constitution](../../architecture/07-testing-constitution.md)
