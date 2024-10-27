<?php

namespace Modules\Jav\Entities;

use Carbon\Carbon;

/**
 * @property string $url
 * @property string $cover
 * @property string $dvd_id
 * @property float $size
 * @property Carbon $date
 * @property array $genres
 * @property string $description
 * @property array $performers
 * @property array $gallery
 */
class OnejavItemEntity
{
    private array $data;

    public function __get($name)
    {
        return $this->data[$name] ?? null;
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __unset($name)
    {
        unset($this->data[$name]);
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
