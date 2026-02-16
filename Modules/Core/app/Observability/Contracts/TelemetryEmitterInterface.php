<?php

namespace Modules\Core\Observability\Contracts;

interface TelemetryEmitterInterface
{
    public function emit(string $eventType, array $context = [], string $level = 'info', ?string $message = null): void;
}
