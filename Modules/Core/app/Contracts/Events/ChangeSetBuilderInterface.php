<?php

declare(strict_types=1);

namespace Modules\Core\Contracts\Events;

use Modules\Core\Dto\Events\ChangeSet;

interface ChangeSetBuilderInterface
{
    /**
     * Build a change set from previous and new state using strict (!==) comparison.
     *
     * @param  array<string, mixed>  $previous
     * @param  array<string, mixed>  $new
     * @param  list<string>|null  $onlyKeys  Optional whitelist; only these keys are considered for diff (security).
     * @param  int  $maxKeys  Maximum number of keys to include (size guard).
     * @param  int  $maxDepth  Maximum depth for nested comparison (1 = top-level only).
     */
    public function build(
        array $previous,
        array $new,
        ?array $onlyKeys = null,
        int $maxKeys = 100,
        int $maxDepth = 1
    ): ChangeSet;
}
