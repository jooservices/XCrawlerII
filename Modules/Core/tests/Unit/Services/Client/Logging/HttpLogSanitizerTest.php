<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Services\Client\Logging;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Modules\Core\Services\Client\Logging\HttpLogSanitizer;
use Modules\Core\Tests\TestCase;

final class HttpLogSanitizerTest extends TestCase
{
    public function test_sanitizes_sensitive_headers_and_body_fields(): void
    {
        $faker = fake();
        $sanitizer = new HttpLogSanitizer(64);
        $token = $faker->sha1();

        $headers = $sanitizer->sanitizeHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ]);

        $body = $sanitizer->sanitizeRequestBody([
            'json' => [
                'token' => $token,
                'name' => $faker->name(),
            ],
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertSame('[REDACTED]', $headers['authorization']);
        $this->assertSame('application/json', $headers['accept']);
        $this->assertIsArray($body['body_preview']);
        $this->assertSame('[REDACTED]', $body['body_preview']['token']);
    }

    public function test_truncates_large_payloads_and_keeps_sha1(): void
    {
        $sanitizer = new HttpLogSanitizer(8);
        $raw = str_repeat('a', 32);

        $body = $sanitizer->sanitizeRequestBody([
            'body' => $raw,
            'headers' => ['Content-Type' => 'text/plain'],
        ]);

        $this->assertTrue($body['body_truncated']);
        $this->assertSame(32, $body['size_bytes']);
        $this->assertSame(sha1($raw), $body['body_sha1']);
    }

    public function test_keeps_stream_pointer_and_skips_unseekable_or_binary_preview(): void
    {
        $sanitizer = new HttpLogSanitizer(64);
        $stream = Utils::streamFor('hello-world');
        $stream->seek(5);

        $request = $sanitizer->sanitizeRequestBody([
            'body' => $stream,
            'headers' => ['Content-Type' => 'text/plain'],
        ]);

        $response = new Response(
            200,
            ['Content-Type' => 'application/octet-stream'],
            Utils::streamFor('binary-data')
        );
        $responseBody = $sanitizer->sanitizeResponseBody($response);

        $this->assertSame(5, $stream->tell());
        $this->assertNotNull($request['body_preview']);
        $this->assertNull($responseBody['body_preview']);
        $this->assertSame(0, $responseBody['size_bytes']);
    }
}
