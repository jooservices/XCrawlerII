<?php

namespace Modules\Jav\Repositories;

use Illuminate\Support\Facades\Event;
use Modules\Jav\Events\OnejavMovieCreatedEvent;
use Modules\Jav\Events\OnejavReferenceCreatedEvent;
use Modules\Jav\Models\OnejavReference;

class OnejavRepository
{
    public function insert(array $data): OnejavReference
    {
        $model = OnejavReference::updateOrCreate([
            'url' => $data['url'],
            'dvd_id' => $data['dvd_id'],
        ], $data);

        Event::dispatch(new OnejavReferenceCreatedEvent($model));

        if ($model->wasRecentlyCreated) {
            OnejavMovieCreatedEvent::dispatch($model);
        }

        return $model;
    }
}
