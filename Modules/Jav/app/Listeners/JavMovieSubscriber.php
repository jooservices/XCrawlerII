<?php

namespace Modules\Jav\Listeners;

use Illuminate\Events\Dispatcher;
use Modules\Jav\Events\JavMovieCreateCompleted;
use Modules\Jav\Events\OnejavReferenceCreatedEvent;
use Modules\Jav\Models\JavMovie;
use Modules\Jav\Notifications\JavMovieCreatedNotification;
use Modules\Jav\Repositories\JavMovieRepository;

class JavMovieSubscriber
{
    public function handleJavMovieCreated(OnejavReferenceCreatedEvent $event): void
    {
        /**
         * @var JavMovie $model
         */
        $model = JavMovie::updateOrCreate([
            'dvd_id' => $event->movie->getDvdId(),
        ], [
            'cover' => $event->movie->getCover(),
            'title' => $event->movie->getTitle(),
            'size' => $event->movie->getSize(),
        ]);

        $repository = app(JavMovieRepository::class);
        $repository->syncPerformers($model, collect($event->movie->getPerformers()));
        $repository->syncGenres($model, collect($event->movie->getGenres()));

        /**
         * @TODO Notification should be enable via configuration
         */
        if (config('jav.onejav.notifications.enabled') ) {
            $model->notify(new JavMovieCreatedNotification());
        }

        JavMovieCreateCompleted::dispatch($model);
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            OnejavReferenceCreatedEvent::class => 'handleJavMovieCreated',
        ];
    }
}
