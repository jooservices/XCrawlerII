<?php

namespace Modules\Core\Observability;

class BlockSignalDetector
{
    /**
     * @return array<string, mixed>|null
     */
    public function detect(array $eventData): ?array
    {
        $status = $this->extractStatusCode($eventData);
        if ($status === null || ! in_array($status, [403, 429, 503], true)) {
            return null;
        }

        $signalType = match ($status) {
            403 => 'forbidden',
            429 => 'rate_limit',
            503 => 'service_unavailable',
        };

        $cooldownSeconds = match ($status) {
            403 => 600,
            429 => 300,
            503 => 120,
        };

        $url = is_string($eventData['url'] ?? null) ? $eventData['url'] : null;
        $site = is_string($eventData['site'] ?? null) ? $eventData['site'] : null;
        $host = 'unknown';

        if (is_string($site) && $site !== '' && $site !== 'unknown') {
            $host = $site;
        } elseif (is_string($url)) {
            $parsedHost = parse_url($url, PHP_URL_HOST);
            if (is_string($parsedHost) && $parsedHost !== '') {
                $host = $parsedHost;
            }
        }

        return [
            'http_status' => $status,
            'block_signal_type' => $signalType,
            'target_host' => is_string($host) ? $host : 'unknown',
            'cooldown_seconds' => $cooldownSeconds,
            'queue' => $eventData['queue'] ?? null,
            'connection' => $eventData['connection'] ?? null,
            'job_name' => $eventData['job_name'] ?? null,
            'worker_host' => $eventData['worker_host'] ?? null,
            'event_type' => $eventData['event_type'] ?? null,
        ];
    }

    private function extractStatusCode(array $eventData): ?int
    {
        $status = null;

        $errorCode = $eventData['error_code'] ?? null;
        if (is_int($errorCode)) {
            $status = $errorCode;
        } else {
            $message = (string) ($eventData['error_message_short'] ?? '');
            if ($message !== '' && preg_match('/\b(403|429|503)\b/', $message, $matches) === 1) {
                $status = (int) $matches[1];
            }
        }

        return $status;
    }
}
