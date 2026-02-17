<?php

namespace Modules\JAV\Services;

use Modules\Core\Facades\Config;

class CrawlerPaginationStateService
{
    /**
     * @return array{current_page: int, current_page_failures: int, consecutive_skips: int}
     */
    public function getState(string $provider, string $type, ?string $legacyKey = null): array
    {
        $raw = (string) Config::get($provider, $this->stateKey($type), '');
        $state = [];
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $state = $decoded;
            }
        }

        $currentPage = (int) ($state['current_page'] ?? 0);
        if ($currentPage <= 0 && $legacyKey !== null) {
            $currentPage = (int) Config::get($provider, $legacyKey, 1);
        }

        $currentPage = $currentPage > 0 ? $currentPage : 1;

        return [
            'current_page' => $currentPage,
            'current_page_failures' => max(0, (int) ($state['current_page_failures'] ?? 0)),
            'consecutive_skips' => max(0, (int) ($state['consecutive_skips'] ?? 0)),
        ];
    }

    public function recordSuccess(string $provider, string $type, int $currentPage, bool $hasNextPage, ?string $legacyKey = null): array
    {
        $nextPage = $hasNextPage ? $currentPage + 1 : 1;
        $state = [
            'current_page' => $nextPage,
            'current_page_failures' => 0,
            'consecutive_skips' => 0,
        ];

        $this->persistState($provider, $type, $state);
        $this->syncLegacyKey($provider, $legacyKey, $nextPage);

        return $state;
    }

    /**
     * @return array{state: array{current_page: int, current_page_failures: int, consecutive_skips: int}, action: string}
     */
    public function recordFailure(
        string $provider,
        string $type,
        int $currentPage,
        int $retryLimit,
        int $jumpLimit,
        bool $countAsTail,
        ?string $legacyKey = null
    ): array {
        $state = $this->getState($provider, $type, $legacyKey);

        if ($state['current_page'] !== $currentPage) {
            $state['current_page'] = $currentPage;
            $state['current_page_failures'] = 0;
        }

        $state['current_page_failures'] += 1;

        if ($state['current_page_failures'] < $retryLimit) {
            $this->persistState($provider, $type, $state);
            $this->syncLegacyKey($provider, $legacyKey, $state['current_page']);

            return ['state' => $state, 'action' => 'retry_same'];
        }

        $state['current_page_failures'] = 0;
        $state['current_page'] = $currentPage + 1;

        if ($countAsTail) {
            $state['consecutive_skips'] += 1;
        }

        if ($jumpLimit > 0 && $state['consecutive_skips'] >= $jumpLimit) {
            $state['current_page'] = 1;
            $state['consecutive_skips'] = 0;
            $action = 'reset';
        } else {
            $action = 'advance';
        }

        $this->persistState($provider, $type, $state);
        $this->syncLegacyKey($provider, $legacyKey, $state['current_page']);

        return ['state' => $state, 'action' => $action];
    }

    private function persistState(string $provider, string $type, array $state): void
    {
        Config::set($provider, $this->stateKey($type), json_encode($state));
    }

    private function syncLegacyKey(string $provider, ?string $legacyKey, int $nextPage): void
    {
        if ($legacyKey === null) {
            return;
        }

        Config::set($provider, $legacyKey, (string) $nextPage);
    }

    private function stateKey(string $type): string
    {
        return $type . '_page_state';
    }
}
