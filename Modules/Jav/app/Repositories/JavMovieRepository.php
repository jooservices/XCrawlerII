<?php

namespace Modules\Jav\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Jav\Models\Interfaces\IJavMovie;
use Modules\Jav\Models\JavGenre;
use Modules\Jav\Models\JavMovie;
use Modules\Jav\Models\JavPerformer;

class JavMovieRepository
{
    public function create(IJavMovie $movie): JavMovie
    {
        /**
         * @var JavMovie $model
         */
        $model = JavMovie::updateOrCreate([
            'dvd_id' => $movie->getDvdId(),
        ], [
            'cover' => $movie->getCover(),
            'title' => $movie->getTitle(),
            'size' => $movie->getSize(),
        ]);

        $this->syncPerformers($model, collect($movie->getPerformers()));
        $this->syncGenres($model, collect($movie->getGenres()));

        return $model;
    }

    public function syncGenres(JavMovie $movie, Collection $genres): void
    {
        $genres
            ->map(function ($genre) {
                return [
                    'name' => $genre,
                ];
            })->each(function ($genre) use ($movie) {
                $model = JavGenre::updateOrCreate($genre);
                $movie->genres()->syncWithoutDetaching($model->id);
            });
    }

    public function syncPerformers(JavMovie $movie, Collection $performers): void
    {
        $performers
            ->map(function ($performer) {
                return [
                    'name' => $performer,
                ];
            })->each(function ($performer) use ($movie) {
                $model = JavPerformer::updateOrCreate($performer);
                $movie->performers()->syncWithoutDetaching($model->id);
            });
    }
}
