<?php

declare(strict_types=1);

namespace Modules\Core\Services\Client\Logging;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class HttpLogSanitizer
{
    /**
     * @var array<string, true>
     */
    private const SENSITIVE_HEADERS = [
        'authorization' => true,
        'cookie' => true,
        'set-cookie' => true,
        'x-api-key' => true,
        'proxy-authorization' => true,
        'x-auth-token' => true,
        'x-csrf-token' => true,
    ];

    /**
     * @var array<string, true>
     */
    private const SENSITIVE_BODY_KEYS = [
        'token' => true,
        'access_token' => true,
        'refresh_token' => true,
        'password' => true,
        'passwd' => true,
        'secret' => true,
        'api_key' => true,
        'cookie' => true,
        'authorization' => true,
    ];

    public function __construct(
        private readonly int $previewBytes = 8192,
    ) {
    }

    /**
     * @param array<string, array<int, string>|string> $headers
     * @return array<string, string>
     */
    public function sanitizeHeaders(array $headers): array
    {
        $result = [];

        foreach ($headers as $name => $value) {
            $normalizedName = strtolower((string) $name);
            $line = is_array($value) ? implode(', ', $value) : (string) $value;

            if (isset(self::SENSITIVE_HEADERS[$normalizedName])) {
                $result[$normalizedName] = '[REDACTED]';
                continue;
            }

            $result[$normalizedName] = $line;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function sanitizeRequestBody(array $options): array
    {
        $contentType = $this->resolveRequestContentType($options);
        $isBinary = $this->isBinaryContentType($contentType);

        if ($isBinary) {
            return $this->emptyBodySnapshot();
        }

        if (array_key_exists('json', $options)) {
            $sanitized = $this->sanitizeBodyValue($options['json']);
            return $this->snapshotFromString($this->encodeJson($sanitized), true);
        }

        if (array_key_exists('form_params', $options)) {
            $sanitized = $this->sanitizeBodyValue($options['form_params']);
            return $this->snapshotFromString($this->encodeJson($sanitized), true);
        }

        if (array_key_exists('body', $options)) {
            $body = $options['body'];

            if ($body instanceof StreamInterface) {
                return $this->snapshotFromStream($body, false);
            }

            if (is_string($body)) {
                return $this->snapshotFromString($body, false);
            }
        }

        return $this->emptyBodySnapshot();
    }

    /**
     * @return array<string, mixed>
     */
    public function sanitizeResponseBody(ResponseInterface $response): array
    {
        $contentType = strtolower($response->getHeaderLine('Content-Type'));
        if ($this->isBinaryContentType($contentType)) {
            return $this->emptyBodySnapshot();
        }

        return $this->snapshotFromStream($response->getBody(), false);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function sanitizeBodyValue(mixed $value): mixed
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $key => $item) {
                $normalizedKey = strtolower((string) $key);
                if (isset(self::SENSITIVE_BODY_KEYS[$normalizedKey])) {
                    $out[$key] = '[REDACTED]';
                    continue;
                }
                $out[$key] = $this->sanitizeBodyValue($item);
            }

            return $out;
        }

        if (is_object($value)) {
            return $this->sanitizeBodyValue((array) $value);
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotFromStream(StreamInterface $stream, bool $decodeJsonPreview): array
    {
        if (! $stream->isSeekable()) {
            return [
                'body_preview' => null,
                'body_sha1' => sha1(''),
                'body_truncated' => false,
                'size_bytes' => (int) ($stream->getSize() ?? 0),
            ];
        }

        $originalPosition = $stream->tell();
        $stream->rewind();
        $raw = $stream->getContents();
        $stream->seek($originalPosition);

        return $this->snapshotFromString($raw, $decodeJsonPreview);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotFromString(string $raw, bool $decodeJsonPreview): array
    {
        $sizeBytes = strlen($raw);
        $truncated = $sizeBytes > $this->previewBytes;
        $previewRaw = $truncated ? substr($raw, 0, $this->previewBytes) : $raw;
        $preview = $previewRaw;

        if ($decodeJsonPreview) {
            $decoded = json_decode($previewRaw, true);
            if (is_array($decoded)) {
                $preview = $decoded;
            }
        }

        return [
            'body_preview' => $preview === '' ? null : $preview,
            'body_sha1' => sha1($raw),
            'body_truncated' => $truncated,
            'size_bytes' => $sizeBytes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyBodySnapshot(): array
    {
        return [
            'body_preview' => null,
            'body_sha1' => sha1(''),
            'body_truncated' => false,
            'size_bytes' => 0,
        ];
    }

    /**
     * @param array<string, mixed> $options
     */
    private function resolveRequestContentType(array $options): string
    {
        $headers = $options['headers'] ?? [];
        if (! is_array($headers)) {
            return '';
        }

        foreach ($headers as $name => $value) {
            if (strtolower((string) $name) !== 'content-type') {
                continue;
            }

            return strtolower(is_array($value) ? implode(', ', $value) : (string) $value);
        }

        return '';
    }

    private function isBinaryContentType(string $contentType): bool
    {
        if ($contentType === '') {
            return false;
        }

        $binaryTokens = [
            'application/octet-stream',
            'image/',
            'video/',
            'audio/',
            'application/pdf',
            'multipart/',
        ];

        foreach ($binaryTokens as $token) {
            if (str_contains($contentType, $token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $value
     */
    private function encodeJson(mixed $value): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return is_string($encoded) ? $encoded : '';
    }
}
