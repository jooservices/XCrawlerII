<?php

declare(strict_types=1);

namespace Modules\Core\Services\Events;

use Modules\Core\Contracts\Events\ChangeSetBuilderInterface;
use Modules\Core\Dto\Events\ChangeSet;

final class ChangeSetBuilder implements ChangeSetBuilderInterface
{
    public function build(
        array $previous,
        array $new,
        ?array $onlyKeys = null,
        int $maxKeys = 100,
        int $maxDepth = 1
    ): ChangeSet {
        $keys = $this->collectKeys($previous, $new, $onlyKeys);

        if (count($keys) > $maxKeys) {
            $keys = array_slice($keys, 0, $maxKeys);
        }

        $changedFields = [];
        $previousPartial = [];
        $newPartial = [];

        foreach ($keys as $key) {
            $prevExists = array_key_exists($key, $previous);
            $newExists = array_key_exists($key, $new);

            $prevVal = $prevExists ? $previous[$key] : null;
            $newVal = $newExists ? $new[$key] : null;

            if (!$prevExists && $newExists) {
                $changedFields[] = $key;
                $newPartial[$key] = $newVal;

                continue;
            }

            if ($prevExists && !$newExists) {
                $changedFields[] = $key;
                $previousPartial[$key] = $prevVal;

                continue;
            }

            if ($maxDepth > 1 && is_array($prevVal) && is_array($newVal)) {
                $nested = $this->build($prevVal, $newVal, null, $maxKeys, $maxDepth - 1);
                if ($nested->hasChanges()) {
                    $changedFields[] = $key;
                    $previousPartial[$key] = $nested->previousPartial;
                    $newPartial[$key] = $nested->newPartial;
                }
            } else {
                if ($this->strictDiff($prevVal, $newVal)) {
                    $changedFields[] = $key;
                    $previousPartial[$key] = $prevVal;
                    $newPartial[$key] = $newVal;
                }
            }
        }

        return new ChangeSet(
            $changedFields,
            $previousPartial === [] ? null : $previousPartial,
            $newPartial === [] ? null : $newPartial
        );
    }

    /**
     * @param  list<string>|null  $onlyKeys
     * @return list<string>
     */
    private function collectKeys(array $previous, array $new, ?array $onlyKeys): array
    {
        $all = array_unique(array_merge(array_keys($previous), array_keys($new)));

        if ($onlyKeys !== null) {
            $allowed = array_flip($onlyKeys);

            return array_values(array_filter($all, static fn (string $k): bool => isset($allowed[$k])));
        }

        return array_values($all);
    }

    private function strictDiff(mixed $a, mixed $b): bool
    {
        if ($a === $b) {
            return false;
        }

        return $a !== $b;
    }
}
