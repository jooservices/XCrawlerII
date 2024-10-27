<?php

namespace Modules\Client\Interfaces;

interface IResponse
{
    public function getStatusCode(): int;

    public function isSuccess(): bool;

    public function getBody(): string;

    public function parseBody(): mixed;

    public function getContentType(): string;

    public function toJson(): string;
}
