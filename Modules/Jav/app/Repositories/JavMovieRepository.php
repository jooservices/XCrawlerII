<?php

namespace Modules\Jav\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Jav\Models\Interfaces\IJavMovie;
use Modules\Jav\Models\JavMovie;

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
                    'uuid' => Str::orderedUuid()->toString(),
                    'name' => $genre,
                ];
            })->each(function ($genre) use ($movie) {
                $movie->genres()->create($genre);
            });
    }

    public function syncPerformers(JavMovie $movie, Collection $performers): void
    {
        $performers
            ->map(function ($performer) {
                return [
                    'uuid' => Str::orderedUuid()->toString(),
                    'name' => $performer,
                ];
            })->each(function ($performer) use ($movie) {
                $movie->performers()->create($performer);
            });
    }
}
