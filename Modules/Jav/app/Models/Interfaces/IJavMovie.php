<?php

namespace Modules\Jav\Models\Interfaces;

interface IJavMovie
{
    public function getCover(): ?string;

    public function getTitle(): ?string;

    public function getDvdId(): ?string;

    public function getSize(): ?float;

    public function getGenres(): ?array;

    public function getPerformers(): ?array;

    public function getGallery(): ?array;
}
