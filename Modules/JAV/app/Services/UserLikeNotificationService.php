<?php

namespace Modules\JAV\Services;

use Illuminate\Support\Collection;
use Modules\JAV\Events\UserLikeMatchedJav;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Interaction;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;
use Modules\JAV\Models\UserLikeNotification;

class UserLikeNotificationService
{
    /**
     * Create user notifications for a newly created JAV based on actor/tag likes.
     */
    public function notifyForJav(Jav $jav): int
    {
        $jav->loadMissing(['actors:id,name', 'tags:id,name']);

        $actorIds = $jav->actors->pluck('id')->all();
        $tagIds = $jav->tags->pluck('id')->all();

        $actorType = Interaction::morphTypeFor(Actor::class);
        $tagType = Interaction::morphTypeFor(Tag::class);

        if ($actorIds === [] && $tagIds === []) {
            return 0;
        }

        $actorNameById = $jav->actors->pluck('name', 'id');
        $tagNameById = $jav->tags->pluck('name', 'id');

        $favorites = Interaction::query()
            ->select(['user_id', 'item_type', 'item_id'])
            ->where('action', Interaction::ACTION_FAVORITE)
            ->where(function ($query) use ($actorIds, $tagIds, $actorType, $tagType): void {
                if ($actorIds !== []) {
                    $query->where(function ($subQuery) use ($actorIds, $actorType): void {
                        $subQuery
                            ->where('item_type', $actorType)
                            ->whereIn('item_id', $actorIds);
                    });
                }

                if ($tagIds !== []) {
                    $method = $actorIds !== [] ? 'orWhere' : 'where';
                    $query->{$method}(function ($subQuery) use ($tagIds, $tagType): void {
                        $subQuery
                            ->where('item_type', $tagType)
                            ->whereIn('item_id', $tagIds);
                    });
                }
            })
            ->get();

        if ($favorites->isEmpty()) {
            return 0;
        }

        $reasonsByUser = $this->groupReasonsByUser($favorites, $actorNameById, $tagNameById);
        $createdCount = 0;

        foreach ($reasonsByUser as $userId => $reasons) {
            $dedupeKey = sprintf('user:%d|jav:%d|type:like_match', (int) $userId, $jav->id);

            $notification = UserLikeNotification::firstOrCreate(
                ['dedupe_key' => $dedupeKey],
                [
                    'user_id' => (int) $userId,
                    'jav_id' => $jav->id,
                    'title' => 'New movie matches your likes',
                    'message' => sprintf('%s %s', $jav->code ?? '', $jav->title ?? ''),
                    'payload' => $reasons,
                ]
            );

            if (! $notification->wasRecentlyCreated) {
                continue;
            }

            $createdCount++;
            UserLikeMatchedJav::dispatch($notification);
        }

        return $createdCount;
    }

    /**
    * @param  Collection<int, Interaction>  $favorites
     * @param  Collection<int, string>  $actorNameById
     * @param  Collection<int, string>  $tagNameById
     * @return array<int, array{matched_actors: array<int, string>, matched_tags: array<int, string>}>
     */
    private function groupReasonsByUser(Collection $favorites, Collection $actorNameById, Collection $tagNameById): array
    {
        $reasonsByUser = [];
        $actorType = Interaction::morphTypeFor(Actor::class);
        $tagType = Interaction::morphTypeFor(Tag::class);

        foreach ($favorites as $favorite) {
            $userId = (int) $favorite->user_id;

            if (! array_key_exists($userId, $reasonsByUser)) {
                $reasonsByUser[$userId] = [
                    'matched_actors' => [],
                    'matched_tags' => [],
                ];
            }

            if ($favorite->item_type === $actorType) {
                $name = $actorNameById->get($favorite->item_id);
                if ($name !== null) {
                    $reasonsByUser[$userId]['matched_actors'][] = $name;
                }
            }

            if ($favorite->item_type === $tagType) {
                $name = $tagNameById->get($favorite->item_id);
                if ($name !== null) {
                    $reasonsByUser[$userId]['matched_tags'][] = $name;
                }
            }
        }

        foreach ($reasonsByUser as $userId => $reasons) {
            $reasonsByUser[$userId]['matched_actors'] = array_values(array_unique($reasons['matched_actors']));
            $reasonsByUser[$userId]['matched_tags'] = array_values(array_unique($reasons['matched_tags']));
        }

        return $reasonsByUser;
    }
}
