<?php

namespace Modules\Client\Services;

use Exception;
use Modules\Client\Exceptions\RequestLogWithoutResponding;
use Modules\Client\Models\RequestLog;

class RequestLogService
{
    private ?RequestLog $model;

    final public function request(
        string $method,
        string $endpoint,
        array $payload,
        ?int $statusCode = null,
        mixed $body = null,
    ): RequestLog {
        if (isset($this->model)) {
            throw new RequestLogWithoutResponding();
        }

        $this->model = RequestLog::create([
            'method' => $method,
            'endpoint' => $endpoint,
            'payload' => $payload,
            'status_code' => $statusCode,
            'body' => $body,
        ]);

        return $this->model;
    }

    final public function respond(
        ?int $statusCode = null,
        mixed $body = null
    ): void {
        $this->model->update([
            'status_code' => $statusCode,
            'body' => $body,
        ]);

        unset($this->model);
    }

    final public function exception(Exception $exception): void
    {
        unset($this->model);
    }
}
