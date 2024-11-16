<?php

namespace Modules\Jav\Listeners;

use Illuminate\Events\Dispatcher;
use Modules\Jav\Events\JavMovieCreateCompleted;
use Modules\Jav\Events\OnejavReferenceCreatedEvent;
use Modules\Jav\Notifications\JavMovieCreatedNotification;
use Modules\Jav\Repositories\JavMovieRepository;

class JavMovieSubscriber
{
    public function onJavMovieCreated(OnejavReferenceCreatedEvent $event): void
    {
        $model = app(JavMovieRepository::class)->create($event->movie);

        if (config('jav.onejav.notifications.enabled', false)) {
            $model->notify(new JavMovieCreatedNotification());
        }

        JavMovieCreateCompleted::dispatch($model);
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            OnejavReferenceCreatedEvent::class => 'onJavMovieCreated',
        ];
    }
}
