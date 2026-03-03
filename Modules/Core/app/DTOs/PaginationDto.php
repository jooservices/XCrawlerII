<?php

declare(strict_types=1);

namespace Modules\Core\DTOs;

use InvalidArgumentException;

final readonly class PaginationDto
{
    public function __construct(
        public int $currentPage,
        public int $perPage,
        public bool $hasNextPage,
        public ?int $nextPage = null,
        public ?int $totalPages = null,
        public ?int $totalItems = null,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        $this->validatePageBounds();
        $this->validateNextPageConsistency();
        $this->validateTotals();
    }

    private function validatePageBounds(): void
    {
        if ($this->currentPage < 1) {
            throw new InvalidArgumentException('currentPage must be >= 1.');
        }
        if ($this->perPage < 1) {
            throw new InvalidArgumentException('perPage must be >= 1.');
        }
    }

    private function validateNextPageConsistency(): void
    {
        if ($this->hasNextPage) {
            if ($this->nextPage === null) {
                throw new InvalidArgumentException('nextPage must be not null when hasNextPage is true.');
            }
            if ($this->nextPage < 1) {
                throw new InvalidArgumentException('nextPage must be >= 1 when hasNextPage is true.');
            }

            return;
        }
        if ($this->nextPage !== null) {
            throw new InvalidArgumentException('nextPage must be null when hasNextPage is false.');
        }
    }

    private function validateTotals(): void
    {
        if ($this->totalPages !== null) {
            if ($this->totalPages < 0) {
                throw new InvalidArgumentException('totalPages must be >= 0.');
            }
            if ($this->totalPages > 0 && $this->currentPage > $this->totalPages) {
                throw new InvalidArgumentException('currentPage must be <= totalPages when totalPages is provided.');
            }
        }
        if ($this->totalItems !== null && $this->totalItems < 0) {
            throw new InvalidArgumentException('totalItems must be >= 0.');
        }
    }

    public function nextPage(int $default = 1): int
    {
        if ($default < 1) {
            throw new InvalidArgumentException('Default must be >= 1.');
        }
        if (! $this->hasNextPage || $this->nextPage === null) {
            return $default;
        }

        return $this->nextPage;
    }
}
