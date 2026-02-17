<?php

namespace Modules\JAV\Services;

use Modules\Core\Facades\Config;

class CrawlerStatusPolicyService
{
    /**
     * @return array{action: string, delay_sec: int, count_as_tail: bool}
     */
    public function resolvePolicy(?int $statusCode, bool $isEmpty): array
    {
        $policies = $this->getPolicies();
        $key = $isEmpty ? 'empty' : (string) ($statusCode ?? 'unknown');

        if (array_key_exists($key, $policies)) {
            return $this->normalizePolicy($policies[$key]);
        }

        return $this->normalizePolicy($policies['default'] ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    private function getPolicies(): array
    {
        $raw = (string) Config::get('crawling', 'status_code_action', '');

        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [
            'empty' => [
                'action' => 'skip',
                'delay_sec' => 0,
                'count_as_tail' => true,
            ],
            '404' => [
                'action' => 'skip',
                'delay_sec' => 0,
                'count_as_tail' => true,
            ],
            '410' => [
                'action' => 'skip',
                'delay_sec' => 0,
                'count_as_tail' => true,
            ],
            '429' => [
                'action' => 'cooldown',
                'delay_sec' => 1800,
                'count_as_tail' => false,
            ],
            '500' => [
                'action' => 'retry',
                'delay_sec' => 120,
                'count_as_tail' => false,
            ],
            '503' => [
                'action' => 'retry',
                'delay_sec' => 120,
                'count_as_tail' => false,
            ],
            'default' => [
                'action' => 'retry',
                'delay_sec' => 60,
                'count_as_tail' => false,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $policy
     * @return array{action: string, delay_sec: int, count_as_tail: bool}
     */
    private function normalizePolicy(array $policy): array
    {
        $action = strtolower((string) ($policy['action'] ?? 'retry'));
        $delay = (int) ($policy['delay_sec'] ?? 0);
        $countAsTail = (bool) ($policy['count_as_tail'] ?? false);

        if (! in_array($action, ['retry', 'cooldown', 'skip'], true)) {
            $action = 'retry';
        }

        return [
            'action' => $action,
            'delay_sec' => $delay,
            'count_as_tail' => $countAsTail,
        ];
    }
}
