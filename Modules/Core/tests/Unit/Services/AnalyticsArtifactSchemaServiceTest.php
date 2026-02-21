<?php

namespace Modules\Core\Tests\Unit\Services;

use Illuminate\Support\Carbon;
use Modules\Core\Services\AnalyticsArtifactSchemaService;
use Modules\Core\Tests\TestCase;

class AnalyticsArtifactSchemaServiceTest extends TestCase
{
    private AnalyticsArtifactSchemaService $schema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schema = new AnalyticsArtifactSchemaService;
    }

    // ──────────────────────────────────────────
    // parityPayload: happy path
    // ──────────────────────────────────────────

    public function test_parity_payload_returns_correct_structure(): void
    {
        Carbon::setTestNow('2026-02-21 10:00:00');

        $result = [
            'checked' => 100,
            'mismatches' => 2,
            'rows' => [['entity_id' => 'abc', 'delta' => 1]],
        ];

        $payload = $this->schema->parityPayload('2026-02-20', 500, $result);

        $this->assertSame(AnalyticsArtifactSchemaService::SCHEMA_VERSION, $payload['schema_version']);
        $this->assertSame('parity', $payload['artifact_type']);
        $this->assertSame('2026-02-20', $payload['artifact_date']);
        $this->assertSame(500, $payload['limit']);
        $this->assertSame(100, $payload['checked']);
        $this->assertSame(2, $payload['mismatches']);
        $this->assertCount(1, $payload['rows']);
        $this->assertNotEmpty($payload['generated_at']);
    }

    public function test_parity_payload_uses_custom_generated_at(): void
    {
        $custom = '2026-01-01T00:00:00+00:00';
        $payload = $this->schema->parityPayload('2026-01-01', 10, ['checked' => 0, 'mismatches' => 0, 'rows' => []], $custom);

        $this->assertSame($custom, $payload['generated_at']);
    }

    public function test_parity_payload_defaults_empty_result(): void
    {
        $payload = $this->schema->parityPayload('2026-02-20', 10, []);

        $this->assertSame(0, $payload['checked']);
        $this->assertSame(0, $payload['mismatches']);
        $this->assertSame([], $payload['rows']);
    }

    // ──────────────────────────────────────────
    // rollbackPayload: happy path
    // ──────────────────────────────────────────

    public function test_rollback_payload_returns_correct_structure(): void
    {
        Carbon::setTestNow('2026-02-21 10:00:00');

        $payload = $this->schema->rollbackPayload(true, false, ['note1']);

        $this->assertSame(AnalyticsArtifactSchemaService::SCHEMA_VERSION, $payload['schema_version']);
        $this->assertSame('rollback', $payload['artifact_type']);
        $this->assertTrue($payload['ingest_route_present']);
        $this->assertFalse($payload['legacy_movie_view_route_present']);
        $this->assertSame(['note1'], $payload['notes']);
        $this->assertNotEmpty($payload['generated_at']);
    }

    public function test_rollback_payload_defaults_empty_notes(): void
    {
        $payload = $this->schema->rollbackPayload(false, true);

        $this->assertSame([], $payload['notes']);
    }

    // ──────────────────────────────────────────
    // validatePayload: happy path
    // ──────────────────────────────────────────

    public function test_validate_valid_parity_payload_returns_no_errors(): void
    {
        $payload = [
            'schema_version' => AnalyticsArtifactSchemaService::SCHEMA_VERSION,
            'artifact_type' => 'parity',
            'generated_at' => '2026-02-21T10:00:00+00:00',
            'artifact_date' => '2026-02-20',
            'limit' => 500,
            'checked' => 100,
            'mismatches' => 0,
            'rows' => [],
        ];

        $this->assertSame([], $this->schema->validatePayload($payload));
    }

    public function test_validate_valid_rollback_payload_returns_no_errors(): void
    {
        $payload = [
            'schema_version' => AnalyticsArtifactSchemaService::SCHEMA_VERSION,
            'artifact_type' => 'rollback',
            'generated_at' => '2026-02-21T10:00:00+00:00',
            'ingest_route_present' => true,
            'legacy_movie_view_route_present' => false,
            'notes' => [],
        ];

        $this->assertSame([], $this->schema->validatePayload($payload));
    }

    // ──────────────────────────────────────────
    // validatePayload: unhappy path
    // ──────────────────────────────────────────

    public function test_validate_missing_schema_version(): void
    {
        $errors = $this->schema->validatePayload([
            'artifact_type' => 'parity',
            'generated_at' => 'now',
            'artifact_date' => '2026-02-20',
            'limit' => 1,
            'checked' => 0,
            'mismatches' => 0,
            'rows' => [],
        ]);

        $this->assertContains('schema_version is missing or unsupported', $errors);
    }

    public function test_validate_wrong_schema_version(): void
    {
        $errors = $this->schema->validatePayload([
            'schema_version' => 'wrong-version',
            'artifact_type' => 'parity',
        ]);

        $this->assertContains('schema_version is missing or unsupported', $errors);
    }

    public function test_validate_invalid_artifact_type_returns_early(): void
    {
        $errors = $this->schema->validatePayload([
            'schema_version' => AnalyticsArtifactSchemaService::SCHEMA_VERSION,
            'artifact_type' => 'invalid',
        ]);

        $this->assertContains('artifact_type must be parity or rollback', $errors);
        // Should return early, no parity-specific or rollback-specific errors
        $this->assertCount(1, $errors);
    }

    public function test_validate_missing_generated_at(): void
    {
        $errors = $this->schema->validatePayload([
            'schema_version' => AnalyticsArtifactSchemaService::SCHEMA_VERSION,
            'artifact_type' => 'parity',
            'artifact_date' => '2026-02-20',
            'limit' => 1,
            'checked' => 0,
            'mismatches' => 0,
            'rows' => [],
        ]);

        $this->assertContains('generated_at must be string', $errors);
    }

    // ──────────────────────────────────────────
    // validatePayload: parity-specific errors
    // ──────────────────────────────────────────

    public function test_validate_parity_missing_required_fields(): void
    {
        $errors = $this->schema->validatePayload([
            'schema_version' => AnalyticsArtifactSchemaService::SCHEMA_VERSION,
            'artifact_type' => 'parity',
            'generated_at' => 'now',
            // missing: artifact_date, limit, checked, mismatches, rows
        ]);

        $this->assertContains('artifact_date must be string', $errors);
        $this->assertContains('limit must be int', $errors);
        $this->assertContains('checked must be int', $errors);
        $this->assertContains('mismatches must be int', $errors);
        $this->assertContains('rows must be array', $errors);
    }

    // ──────────────────────────────────────────
    // validatePayload: rollback-specific errors
    // ──────────────────────────────────────────

    public function test_validate_rollback_missing_required_fields(): void
    {
        $errors = $this->schema->validatePayload([
            'schema_version' => AnalyticsArtifactSchemaService::SCHEMA_VERSION,
            'artifact_type' => 'rollback',
            'generated_at' => 'now',
            // missing: ingest_route_present, legacy_movie_view_route_present, notes
        ]);

        $this->assertContains('ingest_route_present must be bool', $errors);
        $this->assertContains('legacy_movie_view_route_present must be bool', $errors);
        $this->assertContains('notes must be array', $errors);
    }

    // ──────────────────────────────────────────
    // Roundtrip: generate → validate
    // ──────────────────────────────────────────

    public function test_parity_payload_passes_own_validation(): void
    {
        Carbon::setTestNow('2026-02-21 10:00:00');

        $payload = $this->schema->parityPayload('2026-02-20', 100, [
            'checked' => 50,
            'mismatches' => 1,
            'rows' => [['id' => 1]],
        ]);

        $this->assertSame([], $this->schema->validatePayload($payload));
    }

    public function test_rollback_payload_passes_own_validation(): void
    {
        Carbon::setTestNow('2026-02-21 10:00:00');

        $payload = $this->schema->rollbackPayload(true, true, ['all good']);

        $this->assertSame([], $this->schema->validatePayload($payload));
    }
}
