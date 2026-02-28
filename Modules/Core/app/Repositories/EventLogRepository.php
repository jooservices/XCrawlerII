<?php

declare(strict_types=1);

namespace Modules\Core\Repositories;

use Modules\Core\Models\MongoDb\EventLog;

/**
 * 1:1 with EventLog model. Persistence only; no business logic.
 */
final class EventLogRepository
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): EventLog
    {
        $model = new EventLog;
        $model->fill($attributes);
        $model->save();

        return $model;
    }

    public function save(EventLog $model): EventLog
    {
        $model->save();

        return $model;
    }
}
