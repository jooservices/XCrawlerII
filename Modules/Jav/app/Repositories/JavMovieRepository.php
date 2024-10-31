<?php

namespace Modules\Jav\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Jav\Models\JavMovie;

class JavMovieRepository
{
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
