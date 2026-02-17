<?php

namespace Modules\JAV\Exceptions;

class CrawlerDelayException extends \RuntimeException
{
    public function __construct(
        private readonly int $delaySeconds,
        private readonly string $action,
        string $message = ''
    ) {
        parent::__construct($message === '' ? 'Crawler delay requested' : $message);
    }

    public static function forRetry(int $delaySeconds, string $message = ''): self
    {
        return new self($delaySeconds, 'retry', $message);
    }

    public static function forCooldown(int $delaySeconds, string $message = ''): self
    {
        return new self($delaySeconds, 'cooldown', $message);
    }

    public function delaySeconds(): int
    {
        return $this->delaySeconds;
    }

    public function action(): string
    {
        return $this->action;
    }
}
