# 08 - Quality Toolchain

## Tool Priority
Rule `08-QLT-001`:
Formatting/style conflicts are resolved in this order: `Pint > PHPCS > PHPMD`.

Rationale:
Single source of truth prevents style deadlock.

Allowed:
```text
Pint changes accepted; PHPCS rule tuned to avoid conflict.
```

Forbidden:
```text
Reject Pint output to satisfy conflicting PHPCS rule.
```

Verification:
- PHPCS/PHPMD configs explicitly exclude conflicting checks.

## Pint Strictness
Rule `08-QLT-002`:
Run Pint with the `laravel` preset (the project default) and treat formatting failures as blocking. Changing preset requires exception registration.

Rationale:
Pint is opinionated and is installed by default in Laravel applications; auto-fixable style drift should never remain in branch state.

Allowed:
```bash
./vendor/bin/pint --test
```

Forbidden:
```bash
./vendor/bin/pint   # run but ignore result
```

Verification:
- Local/CI command exits non-zero on style violations.

## PHPStan + Larastan
Rule `08-QLT-003`:
Set PHPStan at max feasible strictness (target level 9), fix types first, avoid ignores except documented temporary exceptions.

Rationale:
Type certainty prevents runtime defects.

Allowed:
```neon
parameters:
  level: 9
```

Forbidden:
```neon
ignoreErrors:
  - '#.*#'
```

Verification:
- Ignore entries are minimal, scoped, and linked to exception IDs.

## PHPMD Tuning
Rule `08-QLT-004`:
PHPMD remains enabled for complexity/clean-code issues, tuned to avoid duplicate style checks already enforced by Pint.

Rationale:
Signal quality must stay high; duplicated noise reduces adoption.

Allowed:
```xml
<exclude name="CamelCaseVariableName" />
```

Forbidden:
```xml
<!-- keep all style checks and conflict with Pint -->
```

Verification:
- PHPMD violations focus on design/complexity, not formatting.

## Enforcement Gates
Rule `08-QLT-005`:
Quality rules MUST be enforced by automated gates: pre-commit runs Pint, and CI runs Pint + PHPCS + PHPStan/Larastan + PHPMD.

Rationale:
Automated enforcement prevents local-only compliance drift.

Allowed:
```text
Pre-commit: pint --test
CI: pint --test + phpcs + phpstan + phpmd
```

Forbidden:
```text
Manual quality checks only, with no automated gate.
```

Verification:
- Repository docs/scripts define required pre-commit and CI quality commands.
- Tool execution order preserves Pint-first conflict policy.

## PHPCS/PHPMD Strictness Target
Rule `08-QLT-006`:
PHPCS baseline is PSR-12 + approved project sniffs; PHPMD must enable `cleancode`, `codesize`, `design`, `naming`, and `unusedcode` rulesets (with approved exclusions only). Disabling an entire PHPCS sniff set or PHPMD ruleset requires exception registration.

Rationale:
Strict baselines keep quality consistent while allowing governed exceptions.

Allowed:
```xml
<rule ref="PSR12" />
<rule ref="rulesets/design.xml" />
```

Forbidden:
```xml
<!-- disable full design ruleset without exception -->
<!-- <rule ref="rulesets/design.xml" /> removed -->
```

Verification:
- Config changes to PHPCS/PHPMD include explicit justification in PR/spec.
- Whole-ruleset disablement references approved exception ID.

## Configuration Templates

`pint.json`
```json
{
  "preset": "laravel",
  "exclude": [
    "bootstrap/cache",
    "storage",
    "vendor"
  ],
  "rules": {
    "no_unused_imports": true,
    "ordered_imports": {
      "sort_algorithm": "alpha"
    },
    "concat_space": {
      "spacing": "one"
    }
  }
}
```

`phpcs.xml`
```xml
<?xml version="1.0"?>
<ruleset name="XCrawler">
  <description>PHPCS rules aligned with Pint priority.</description>
  <file>app</file>
  <file>Modules</file>
  <exclude-pattern>*/vendor/*</exclude-pattern>
  <exclude-pattern>*/storage/*</exclude-pattern>
  <rule ref="PSR12" />
  <exclude name="Squiz.WhiteSpace.SuperfluousWhitespace" />
  <exclude name="Generic.Formatting.MultipleStatementAlignment" />
</ruleset>
```

`phpstan.neon`
```neon
includes:
  - vendor/nunomaduro/larastan/extension.neon

parameters:
  paths:
    - app
    - Modules
  level: 9
  checkMissingIterableValueType: true
  checkGenericClassInNonGenericObjectType: true
  treatPhpDocTypesAsCertain: true
  reportUnmatchedIgnoredErrors: true
  ignoreErrors: []
```

`phpmd.xml`
```xml
<?xml version="1.0"?>
<ruleset name="XCrawler PHPMD">
  <description>PHPMD tuned for architecture signal, not style duplication.</description>
  <rule ref="rulesets/cleancode.xml" />
  <rule ref="rulesets/codesize.xml" />
  <rule ref="rulesets/design.xml" />
  <rule ref="rulesets/naming.xml">
    <exclude name="ShortVariable" />
    <exclude name="LongVariable" />
  </rule>
  <rule ref="rulesets/unusedcode.xml" />
  <exclude-pattern>*/vendor/*</exclude-pattern>
  <exclude-pattern>*/storage/*</exclude-pattern>
</ruleset>
```
