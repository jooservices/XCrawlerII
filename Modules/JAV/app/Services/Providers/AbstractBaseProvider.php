<?php

declare(strict_types=1);

namespace Modules\JAV\Services\Providers;

use Modules\JAV\Contracts\MovieAdapterInterface;
use Modules\JAV\DTOs\MovieDto;

abstract class AbstractBaseProvider implements MovieAdapterInterface
{
    public function save(MovieDto $dto): void
    {
        $movie = $this->dtoToMovieArray($dto);
        $tags = $this->dtoToTagsArray($dto);
        $actors = $this->dtoToActorsArray($dto);

        $this->upsertByCode($dto->code, [
            'movie' => $movie,
            'tags' => $tags,
            'actors' => $actors,
        ]);
    }

    /** @param array<string, mixed> $payload */
    protected function upsertByCode(string $code, array $payload): void
    {
        $repository = $this->repository();
        $repository->upsertByCode($code, $payload);
    }

    abstract protected function repository(): object;

    /** @return array<string, mixed> */
    protected function dtoToMovieArray(MovieDto $dto): array
    {
        return [
            'item_id' => $dto->itemId,
            'title' => $dto->title,
            'description' => $dto->description,
            'category' => $dto->category,
            'cover' => $dto->cover,
            'trailer' => $dto->trailer,
            'gallery' => $dto->gallery,
            'is_censored' => $dto->isCensored,
            'has_subtitles' => $dto->hasSubtitles,
            'subtitles' => $dto->subtitles,
            'release_date' => $dto->releaseDate?->format('Y-m-d'),
            'duration_minutes' => $dto->durationMinutes,
            'crawled_at' => $dto->crawledAt?->format(\DateTimeInterface::ATOM),
            'seen_at' => $dto->seenAt?->format(\DateTimeInterface::ATOM),
            'attributes' => $dto->attributes,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    protected function dtoToTagsArray(MovieDto $dto): array
    {
        $out = [];
        foreach ($dto->tags as $tag) {
            $out[] = [
                'name' => $tag->name,
                'description' => $tag->description,
            ];
        }

        return $out;
    }

    /** @return array<int, array<string, mixed>> */
    protected function dtoToActorsArray(MovieDto $dto): array
    {
        $out = [];
        foreach ($dto->actors as $actor) {
            $out[] = [
                'name' => $actor->name,
                'avatar' => $actor->avatar,
                'aliases' => $actor->aliases,
                'birth_date' => $actor->birthDate?->format('Y-m-d'),
                'birthplace' => $actor->birthplace,
                'blood_type' => $actor->bloodType,
                'height' => $actor->height,
                'weight' => $actor->weight,
                'bust' => $actor->bust,
                'waist' => $actor->waist,
                'hip' => $actor->hip,
                'cup_size' => $actor->cupSize,
                'hobbies' => $actor->hobbies,
                'skills' => $actor->skills,
                'attributes' => $actor->attributes,
                'crawled_at' => $actor->crawledAt?->format(\DateTimeInterface::ATOM),
                'seen_at' => $actor->seenAt?->format(\DateTimeInterface::ATOM),
            ];
        }

        return $out;
    }
}
