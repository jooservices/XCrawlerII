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
    /**
     * @param OnejavReferenceCreatedEvent $event
     * @return void
     */
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

        $model->notify(new JavMovieCreatedNotification($this));

        JavMovieCreateCompleted::dispatch($model);
        /**
         * @TODO Send notifications
         */
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            OnejavReferenceCreatedEvent::class => 'handleJavMovieCreated',
        ];
    }
}