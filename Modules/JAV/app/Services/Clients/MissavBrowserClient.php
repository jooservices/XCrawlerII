<?php

declare(strict_types=1);

namespace Modules\JAV\Services\Clients;

use Playwright\Playwright;
use Modules\JAV\Support\MissavHtmlGuard;

class MissavBrowserClient
{
    public function __construct(
        private readonly string $baseUrl = 'https://missav.ai'
    ) {
    }

    public function fetchHtml(string $url): string
    {
        $targetUrl = $this->normalizeUrl($url);
        $context = Playwright::chromium([
            'headless' => $this->isHeadless(),
            'args' => $this->playwrightArgs(),
        ]);

        $page = $context->newPage();
        $page->goto($targetUrl, [
            'waitUntil' => $this->waitUntil(),
            'timeout' => $this->timeoutMs(),
        ]);

        $html = $page->content();
        $context->close();

        MissavHtmlGuard::assertNotBlocked($html);

        return $html;
    }

    private function normalizeUrl(string $url): string
    {
        if (preg_match('#^https?://#i', $url) === 1) {
            return $url;
        }

        return rtrim($this->baseUrl, '/').'/'.ltrim($url, '/');
    }

    private function isHeadless(): bool
    {
        $value = config('jav.missav.playwright.headless', false);

        if (is_string($value)) {
            $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            return $parsed ?? false;
        }

        return (bool) $value;
    }

    private function timeoutMs(): int
    {
        $value = (int) config('jav.missav.playwright.timeout_ms', 45000);

        return $value > 0 ? $value : 45000;
    }

    /**
     * @return string[]
     */
    private function playwrightArgs(): array
    {
        $value = config('jav.missav.playwright.args', '--no-sandbox');

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            return preg_split('/\s+/', trim($value)) ?: ['--no-sandbox'];
        }

        return ['--no-sandbox'];
    }

    private function waitUntil(): string
    {
        $value = (string) config('jav.missav.playwright.wait_until', 'domcontentloaded');
        $allowed = ['load', 'domcontentloaded', 'networkidle', 'commit'];

        return in_array($value, $allowed, true) ? $value : 'domcontentloaded';
    }

}
