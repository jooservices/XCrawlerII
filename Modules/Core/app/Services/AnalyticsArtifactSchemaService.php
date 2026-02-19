<?php

namespace Modules\Core\Services;

use Illuminate\Support\Carbon;

/**
 * Provides versioned payload contracts for analytics parity/rollback artifacts.
 */
class AnalyticsArtifactSchemaService
{
    public const SCHEMA_VERSION = 'analytics-artifact.v1';

    /**
     * @param  array{checked:int,mismatches:int,rows:array<int,array<string,mixed>>}  $result
     * @return array<string, mixed>
     */
    public function parityPayload(string $artifactDate, int $limit, array $result, ?string $generatedAt = null): array
    {
        return [
            'schema_version' => self::SCHEMA_VERSION,
            'artifact_type' => 'parity',
            'artifact_date' => $artifactDate,
            'generated_at' => $generatedAt ?? Carbon::now()->toIso8601String(),
            'limit' => $limit,
            'checked' => (int) ($result['checked'] ?? 0),
            'mismatches' => (int) ($result['mismatches'] ?? 0),
            'rows' => array_values($result['rows'] ?? []),
        ];
    }

    /**
     * @param  array<int, string>  $notes
     * @return array<string, mixed>
     */
    public function rollbackPayload(bool $ingestRoutePresent, bool $legacyMovieViewRoutePresent, array $notes = []): array
    {
        return [
            'schema_version' => self::SCHEMA_VERSION,
            'artifact_type' => 'rollback',
            'generated_at' => Carbon::now()->toIso8601String(),
            'ingest_route_present' => $ingestRoutePresent,
            'legacy_movie_view_route_present' => $legacyMovieViewRoutePresent,
            'notes' => array_values($notes),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, string>
     */
    public function validatePayload(array $payload): array
    {
        $errors = [];
        if (($payload['schema_version'] ?? null) !== self::SCHEMA_VERSION) {
            $errors[] = 'schema_version is missing or unsupported';
        }

        $type = (string) ($payload['artifact_type'] ?? '');
        if (! in_array($type, ['parity', 'rollback'], true)) {
            $errors[] = 'artifact_type must be parity or rollback';

            return $errors;
        }

        if (! is_string($payload['generated_at'] ?? null)) {
            $errors[] = 'generated_at must be string';
        }

        if ($type === 'parity') {
            if (! is_string($payload['artifact_date'] ?? null)) {
                $errors[] = 'artifact_date must be string';
            }
            if (! is_int($payload['limit'] ?? null)) {
                $errors[] = 'limit must be int';
            }
            if (! is_int($payload['checked'] ?? null)) {
                $errors[] = 'checked must be int';
            }
            if (! is_int($payload['mismatches'] ?? null)) {
                $errors[] = 'mismatches must be int';
            }
            if (! is_array($payload['rows'] ?? null)) {
                $errors[] = 'rows must be array';
            }
        }

        if ($type === 'rollback') {
            if (! is_bool($payload['ingest_route_present'] ?? null)) {
                $errors[] = 'ingest_route_present must be bool';
            }
            if (! is_bool($payload['legacy_movie_view_route_present'] ?? null)) {
                $errors[] = 'legacy_movie_view_route_present must be bool';
            }
            if (! is_array($payload['notes'] ?? null)) {
                $errors[] = 'notes must be array';
            }
        }

        return $errors;
    }
}
