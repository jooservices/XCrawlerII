<?php

namespace Modules\Core\Observability;

use Carbon\CarbonInterface;

class ObsPayloadMapper
{
    public function __construct(private readonly RedactionService $redactionService) {}

    public function map(string $eventType, array $context = [], string $level = 'info', ?string $message = null): array
    {
        $traceId = $context['traceId'] ?? $context['trace_id'] ?? null;
        $timestamp = $context['timestamp'] ?? now('UTC');

        if ($timestamp instanceof CarbonInterface) {
            $timestamp = $timestamp->copy()->setTimezone('UTC')->toIso8601String();
        } elseif (! is_string($timestamp)) {
            $timestamp = now('UTC')->toIso8601String();
        }

        $tags = [];
        if (isset($context['tags']) && is_array($context['tags'])) {
            $tags = array_values(array_filter($context['tags'], fn ($value): bool => is_string($value) && $value !== ''));
        }

        if (! in_array($eventType, $tags, true)) {
            $tags[] = $eventType;
        }

        $payload = [
            'service' => (string) config('services.obs.service_name', 'xcrawlerii'),
            'env' => (string) config('app.env', 'production'),
            'level' => strtoupper($level),
            'message' => $message ?? ('Operational event: '.$eventType),
            'timestamp' => $timestamp,
            'traceId' => is_string($traceId) ? $traceId : null,
            'context' => $this->redactionService->redact($context),
            'tags' => $tags,
            'eventType' => $eventType,
        ];

        return $this->enforcePayloadSizeLimit($payload);
    }

    private function enforcePayloadSizeLimit(array $payload): array
    {
        $maxBytes = max(0, (int) config('services.obs.max_payload_bytes', 131072));

        if ($maxBytes === 0 || $this->encodedSize($payload) <= $maxBytes) {
            return $payload;
        }

        $originalContextSize = isset($payload['context']) && is_array($payload['context'])
            ? $this->encodedSize($payload['context'])
            : 0;

        $payload['context'] = [
            '_truncated' => true,
            '_reason' => 'payload_too_large',
            '_original_context_bytes' => $originalContextSize,
            '_max_payload_bytes' => $maxBytes,
        ];

        $message = (string) ($payload['message'] ?? '');
        $payload['message'] = mb_substr($message, 0, 500).' [context_truncated]';

        if ($this->encodedSize($payload) > $maxBytes) {
            $payload['tags'] = array_slice((array) ($payload['tags'] ?? []), 0, 5);
            $payload['message'] = mb_substr((string) $payload['message'], 0, 160);
        }

        return $payload;
    }

    private function encodedSize(array $payload): int
    {
        return strlen((string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
    }
}
