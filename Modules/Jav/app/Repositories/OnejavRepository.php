<?php

namespace Modules\Jav\Repositories;

use Illuminate\Support\Facades\Event;
use Modules\Jav\Events\Onejav\MovieCreatedEvent;
use Modules\Jav\Events\Onejav\ReferenceCreatedEvent;
use Modules\Jav\Models\OnejavReference;

class OnejavRepository
{
    public function insert(array $data): OnejavReference
    {
        $model = OnejavReference::updateOrCreate([
            'url' => $data['url'],
            'dvd_id' => $data['dvd_id'],
        ], $data);

        Event::dispatch(new ReferenceCreatedEvent($model));

        if ($model->wasRecentlyCreated) {
            MovieCreatedEvent::dispatch($model);
        }

        return $model;
    }
}
