<?php

declare(strict_types=1);

namespace Modules\Core\Repositories;

use InvalidArgumentException;
use Modules\Core\Models\MongoDb\ClientLog;

/**
 * 1:1 with ClientLog model. Persistence only; no business logic.
 */
final class ClientLogRepository
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): ClientLog
    {
        $this->assertRequiredAttributes($attributes);

        $model = new ClientLog();
        $model->fill($attributes);
        $model->save();

        return $model;
    }

    public function save(ClientLog $model): ClientLog
    {
        $model->save();

        return $model;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function assertRequiredAttributes(array $attributes): void
    {
        $required = ['ts', 'site', 'method', 'path', 'url', 'status'];

        foreach ($required as $key) {
            if (! array_key_exists($key, $attributes)) {
                throw new InvalidArgumentException("Missing required ClientLog attribute: {$key}");
            }
        }
    }
}
