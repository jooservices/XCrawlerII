<?php

declare(strict_types=1);

namespace Modules\JAV\Contracts\Crawling;

use Modules\Core\DTOs\ListDto;
use Modules\JAV\DTOs\MovieDto;

interface CrawlingMovieInterface
{
    public function new(?int $page = null): ListDto;

    public function popular(?int $page = null): ListDto;

    public function daily(?\DateTimeInterface $date = null, ?int $page = null): ListDto;

    public function item(string $codeOrUrl): MovieDto;
}
