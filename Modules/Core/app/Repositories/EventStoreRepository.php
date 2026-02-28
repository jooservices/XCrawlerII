<?php

declare(strict_types=1);

namespace Modules\Core\Repositories;

use Modules\Core\Models\MongoDb\EventStore;

/**
 * 1:1 with EventStore model. Persistence only; no business logic.
 */
final class EventStoreRepository
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): EventStore
    {
        $model = new EventStore;
        $model->fill($attributes);
        $model->save();

        return $model;
    }

    public function save(EventStore $model): EventStore
    {
        $model->save();

        return $model;
    }
}
