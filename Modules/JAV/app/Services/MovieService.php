<?php

declare(strict_types=1);

namespace Modules\JAV\Services;

use Illuminate\Support\Facades\DB;
use Modules\JAV\Contracts\MovieAdapterInterface;
use Modules\JAV\DTOs\MovieDto;
use Modules\JAV\Enums\SourceEnum;
use Modules\JAV\Repositories\ActorRepository;
use Modules\JAV\Repositories\MovieRepository;
use Modules\JAV\Exceptions\UnsupportedSourceException;
use Modules\JAV\Repositories\TagRepository;
use Modules\JAV\Services\Providers\FfJavAdapter;
use Modules\JAV\Services\Providers\OneFourOneJavAdapter;
use Modules\JAV\Services\Providers\OnejavAdapter;

final readonly class MovieService
{
    private const string DATETIME_FORMAT = 'Y-m-d H:i:s';

    public function __construct(
        private OnejavAdapter $onejavAdapter,
        private OneFourOneJavAdapter $oneFourOneJavAdapter,
        private FfJavAdapter $ffJavAdapter,
        private MovieRepository $movieRepository,
        private ActorRepository $actorRepository,
        private TagRepository $tagRepository,
    ) {
    }

    public function save(MovieDto $movie): void
    {
        // 1) Save Mongo per-source snapshot
        $this->resolveAdapter($movie->source)->save($movie);

        // 2) Save canonical MySQL + pivot mapping
        DB::transaction(function () use ($movie): void {
            $savedMovie = $this->movieRepository->upsertByCode($movie->code, [
                'code' => $movie->code,
                'item_id' => $movie->itemId,
                'title' => $movie->title,
                'description' => $movie->description,
                'category' => $movie->category,
                'cover' => $movie->cover,
                'trailer' => $movie->trailer,
                'gallery' => $movie->gallery,
                'is_censored' => $movie->isCensored,
                'has_subtitles' => $movie->hasSubtitles,
                'subtitles' => $movie->subtitles,
                'release_date' => $movie->releaseDate?->format('Y-m-d'),
                'duration_minutes' => $movie->durationMinutes,
                'crawled_at' => $movie->crawledAt?->format(self::DATETIME_FORMAT),
                'seen_at' => $movie->seenAt?->format(self::DATETIME_FORMAT),
                'attributes' => $movie->attributes,
            ]);

            $actorIds = [];
            foreach ($movie->actors as $actorDto) {
                $actor = $this->actorRepository->upsertByName($actorDto->name, array_filter([
                    'name' => $actorDto->name,
                    'avatar' => $actorDto->avatar,
                    'aliases' => $actorDto->aliases,
                    'birth_date' => $actorDto->birthDate?->format('Y-m-d'),
                    'birthplace' => $actorDto->birthplace,
                    'blood_type' => $actorDto->bloodType,
                    'height' => $actorDto->height,
                    'weight' => $actorDto->weight,
                    'bust' => $actorDto->bust,
                    'waist' => $actorDto->waist,
                    'hip' => $actorDto->hip,
                    'cup_size' => $actorDto->cupSize,
                    'hobbies' => $actorDto->hobbies,
                    'skills' => $actorDto->skills,
                    'attributes' => $actorDto->attributes,
                    'crawled_at' => $actorDto->crawledAt?->format(self::DATETIME_FORMAT),
                    'seen_at' => $actorDto->seenAt?->format(self::DATETIME_FORMAT),
                ], fn (mixed $v): bool => $v !== null));
                $actorIds[] = $actor->getKey();
            }

            $tagIds = [];
            foreach ($movie->tags as $tagDto) {
                $tag = $this->tagRepository->upsertByName($tagDto->name, array_filter([
                    'name' => $tagDto->name,
                    'description' => $tagDto->description,
                ], fn (mixed $v): bool => $v !== null));
                $tagIds[] = $tag->getKey();
            }

            $savedMovie->actors()->sync($actorIds);
            $savedMovie->tags()->sync($tagIds);
        });
    }

    private function resolveAdapter(SourceEnum $source): MovieAdapterInterface
    {
        return match ($source) {
            SourceEnum::Onejav => $this->onejavAdapter,
            SourceEnum::OneFourOneJav => $this->oneFourOneJavAdapter,
            SourceEnum::FfJav => $this->ffJavAdapter,
            default => throw UnsupportedSourceException::forSource($source->value),
        };
    }
}
