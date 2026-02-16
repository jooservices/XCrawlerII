<?php

namespace Modules\Core\Observability\Contracts;

interface ObservabilityClientInterface
{
    public function sendLog(array $payload): void;
}
