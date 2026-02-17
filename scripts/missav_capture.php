<?php

declare(strict_types=1);

use Playwright\Playwright;

require __DIR__ . '/../vendor/autoload.php';

$arguments = getopt('', ['url::', 'out::', 'headless::', 'timeout::']);

$url = $arguments['url'] ?? getenv('MISSAV_CAPTURE_URL') ?: 'https://missav.ai/dm590/en/release';
$out = $arguments['out'] ?? getenv('MISSAV_CAPTURE_OUT') ?: __DIR__ . '/../Modules/JAV/tests/Fixtures/missav/missav_new.html';
$headless = ($arguments['headless'] ?? getenv('PLAYWRIGHT_HEADLESS')) === '1';
$timeoutMs = (int) ($arguments['timeout'] ?? getenv('PLAYWRIGHT_TIMEOUT_MS') ?: 45000);
$waitUntil = (string) (getenv('PLAYWRIGHT_WAIT_UNTIL') ?: 'domcontentloaded');
$args = ['--no-sandbox'];
if (($envArgs = getenv('PLAYWRIGHT_ARGS')) !== false && trim($envArgs) !== '') {
    $args = array_values(array_filter(array_map('trim', explode(' ', $envArgs))));
}

$context = Playwright::chromium([
    'headless' => $headless,
    'args' => $args,
]);
$page = $context->newPage();

$page->goto($url, [
    'waitUntil' => $waitUntil,
    'timeout' => $timeoutMs,
]);

$html = $page->content();
$targetDir = dirname($out);
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0775, true);
}

file_put_contents($out, $html);

fwrite(STDOUT, "Saved HTML to {$out}\n");

$context->close();
